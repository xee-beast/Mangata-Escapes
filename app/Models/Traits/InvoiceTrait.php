<?php

namespace App\Models\Traits;

use Illuminate\Database\Eloquent\Relations\Pivot;
use Carbon\Carbon;

trait InvoiceTrait
{
    protected $invoiceAttributes;

    private $occupantsText = [
        '1' => 'SGL',
        '2' => 'DBL',
        '3' => 'TPL',
        '4' => 'QUAD',
        '5' => 'QUINT',
        '6' => 'SEXT',
        '7' => 'SEPT',
        '8' => 'OCT',
        '9' => 'NON',
        '10' => 'DEC',
        '11' => 'UND',
        '12' => 'DUO',
        '13' => 'TRIO',
        '14' => 'QUATTRO',
        '15' => 'QUINTO',
        '16' => 'SESTO',
        '17' => 'SETTIMO',
        '18' => 'OTTAVO',
        '19' => 'NOVO',
        '20' => 'VIGESIMO'
    ];

    public function getNightsAttribute() {
        if (!isset($this->invoiceAttributes['night_breakdown'])) {
            $nights = collect();

            foreach ($this->roomBlocks as $roomBlock) {
                $pivot = $roomBlock->pivot;
                $roomBlockNights = collect();

                for (
                    $date = Carbon::parse($pivot->check_in)->addDay();
                    $date->lessThanOrEqualTo(Carbon::parse($pivot->check_out));
                    $date->addDay()
                ) {
                    $adults = collect();
                    $children = collect();

                    foreach ($this->clients as $client) {
                        foreach ($client->guests as $guest) {
                            if ($date->isAfter($guest->check_in) && $date->lessThanOrEqualTo($guest->check_out)) {
                                if ($roomBlock->room->adults_only) {
                                    $adults->push($guest);
                                    continue;
                                }

                                $age = $guest->birth_date->diffInYears($date);

                                $isChild = ($age < 18) && $roomBlock->child_rates->contains(function ($childRate) use ($age) {
                                    return $age >= $childRate->from && $age <= $childRate->to;
                                });

                                $isChild ? $children->push($guest) : $adults->push($guest);
                            }
                        }
                    }

                    if (!$roomBlock->room->adults_only) {
                        while (
                            $children->count() > ($roomBlock->room->max_children) ||
                            $adults->count() == 0 ||
                            ($children->count() / $adults->count()) > ($roomBlock->max_children_per_adult / $roomBlock->min_adults_per_child)
                        ) {
                            $rate = 0;
                            $children->each(function ($child, $key) use ($date, &$rate, &$highest, $roomBlock) {
                                $age = $child->birth_date->diffInYears($date);

                                $roomBlock->child_rates->each(function ($childRate) use ($age, &$newRate) {
                                    if ($age >= $childRate->from && $age <= $childRate->to) {
                                        $newRate = $childRate->rate;
                                        return false;
                                    }
                                });

                                if ($newRate >= $rate) {
                                    $highest = $key;
                                    $rate = $newRate;
                                }
                            });

                            $adults->push($children->pull($highest));
                        }
                    }

                    $roomBlockNights->push((object) [
                        'date' => clone $date,
                        'adults' => $adults,
                        'children' => $children,
                    ]);
                }

                $nights->push((object) [
                    'room_block_id' => $roomBlock->id,
                    'nights' => $roomBlockNights,
                ]);
            }

            $this->invoiceAttributes['night_breakdown'] = $nights;
        }

        return $this->invoiceAttributes['night_breakdown'];
    }

    public function getSubTotalAttribute() {
        if (!isset($this->invoiceAttributes['sub_total'])) {
            $this->invoiceAttributes['sub_total'] = $this->roomBlocks->reduce(function ($total, $roomBlock) {
                $roomBlockNights = $this->nights->where('room_block_id', $roomBlock->id);

                $roomBlockTotal = $roomBlockNights->reduce(function ($blockTotal, $roomBlockNight) use ($roomBlock) {
                    return $blockTotal + $roomBlockNight->nights->reduce(function ($nightTotal, $night) use ($roomBlock) {
                        $useSplitRate = (!is_null($roomBlock->split_date)) && $night->date->isAfter($roomBlock->split_date);
                        $adultRate = $roomBlock->rates->firstWhere('occupancy', $night->adults->count()) ?? $roomBlock->rates->sortBy('occupancy')->last();
                        $adultTotal =  $night->adults->count() * ($useSplitRate ? $adultRate->split_rate : $adultRate->rate);

                        $childTotal = $night->children->reduce(function ($childTotal, $child) use ($night, $roomBlock, $useSplitRate) {
                            $age = $child->birth_date->diffInYears($night->date);

                            $childRate = $roomBlock->child_rates->first(function ($childRate) use ($age) {
                                return $age >= $childRate->from && $age <= $childRate->to;
                            });

                            return $childTotal + ($childRate ? ($useSplitRate ? $childRate->split_rate : $childRate->rate) : 0);
                        }, 0);

                        return $nightTotal + $adultTotal + $childTotal;
                    }, 0);
                }, 0);

                return $total + $roomBlockTotal;
            }, 0);
        }

        return $this->invoiceAttributes['sub_total'];
    }

    public function getTotalAttribute() {
        if (!isset($this->invoiceAttributes['total'])) {
            $clients = $this->breakdownClients($this->clients);

            $this->invoiceAttributes['total'] = $clients->reduce(function ($total, $client) {
                return $total + $client->total;
            }, 0);            
        }

        return $this->invoiceAttributes['total'];
    }

    public function getClientTotal($client) {
        $clients = collect([$client]);
        $clients = $this->breakdownClients($clients);

        return $clients->reduce(function ($total, $client) {
            return $total + $client->total;
        }, 0);
    }

    public function getPaymentTotalAttribute() {
        if (!isset($this->invoiceAttributes['payment_total'])) {
            $this->invoiceAttributes['payment_total'] = $this->clients->reduce(function ($total, $client) {
                return $total + $client->payments->reduce(function ($total, $payment) {
                    return $total + (is_null($payment->confirmed_at) ? 0 : $payment->amount);
                }, 0);
            }, 0);
        }

        return $this->invoiceAttributes['payment_total'];
    }

    public function getPaymentTotal($client) {
        return $client->payments->reduce(function ($total, $payment) {
            return $total + (is_null($payment->confirmed_at) ? 0 : $payment->amount);
        }, 0);
    }

    public function getInvoiceAttribute() {
        if (!isset($this->invoiceAttributes['invoice'])) {
            $clients = $this->breakdownClients($this->clients);

            if ($this->group) {
                $rooms = $this->roomBlocks->map(function ($roomBlock) {
                    return (object)[
                        'id' => $roomBlock->id,
                        'hotel' => $roomBlock->room->hotel->name,
                        'room' => $roomBlock->room->name,
                        'bedding' => $roomBlock->pivot->bed,
                        'travel_dates' => (object)[
                            'check_in' => $roomBlock->pivot->check_in,
                            'check_out' => $roomBlock->pivot->check_out
                        ]
                    ];
                });
            } else {
                $rooms = $this->roomArrangements->map(function ($roomArrangement) {
                    return (object)[
                        'id' => $roomArrangement->id,
                        'hotel' => $roomArrangement->hotel,
                        'room' => $roomArrangement->room,
                        'bedding' => $roomArrangement->bed,
                        'travel_dates' => (object)[
                            'check_in' => $roomArrangement->check_in,
                            'check_out' => $roomArrangement->check_out
                        ]
                    ];
                });
            }

            $this->invoiceAttributes['invoice'] = (object)[
                'details' => (object)[
                    'group' => $this->group ?? null,
                    'booking' => $this,
                    'rooms' => $rooms,
                    'due_dates' => $this->due_dates,
                ],
                'clients' => $clients,
                'transportation' => $this->group ? (object)[
                    'category' => ($this->group->transportation_type == 'shared' ? 'Shared' : 'Group Private') . ' (RT)',
                ] : null,
                'round_trip_transportation' => $this->group ? (object) [
                    'category' => ($this->group->transportation_type == 'shared' ? 'Shared' : 'Group Private') . ' (RT)',
                ] : null,
                'one_way_transportation' => $this->group ? (object) [
                    'category' => ($this->group->transportation_type == 'shared' ? 'Shared' : 'Group Private') . ' (RT)',
                ] : null,
            ];
        }

        return $this->invoiceAttributes['invoice'];
    }

    public function getMinimumDepositAttribute() {
        if (!isset($this->invoiceAttributes['minimum_deposit'])) {
            $payment = 0;

            if (!$this->group || ($this->group && $this->group->is_fit)) {
                $insurance = $this->clients->reduce(function ($total, $client) {
                    return $total + ($client->fitRate ? $client->fitRate->insurance : 0);
                }, 0);

                if ($this->deposit && $this->deposit_type) {
                    $deposit = $this->deposit;
                    $deposit_type = $this->deposit_type;
                } else if ($this->group) {
                    $deposit = $this->group->deposit;
                    $deposit_type = $this->group->deposit_type;
                } else {
                    $deposit = 0;
                    $deposit_type = 'fixed';
                }
            } else {
                $insurance = $this->getInsuranceRates($this->guests)->reduce(function ($insuranceTotal, $insurance) {
                    return $insuranceTotal + ($insurance->rate * $insurance->quantity);
                }, 0);

                $deposit = $this->group->deposit;
                $deposit_type = $this->group->deposit_type;
            }
    
            if ($deposit_type == 'fixed') {
                $payment += $deposit;
                $payment += $insurance;
            } else if ($deposit_type == 'per person') {
                $payment += ($deposit * $this->guests->count());
                $payment += $insurance;
            } else if ($deposit_type == 'percentage') {
                $clients_total = 0;

                foreach ($this->clients as $client) {
                    $clients_total += $this->getClientTotal($client);
                }

                $clients_total -= $insurance;

                $payment += $clients_total * ($deposit / 100);
                $payment += $insurance;
            } else if ($deposit_type == 'nights' && $this->group && !$this->group->is_fit) {
                $this->invoice->clients->each(function($client) use (&$payment, $deposit) {
                    $nights = $deposit;

                    $client->guests->each(function ($guest) use (&$payment, $nights) {
                        $nightsLeft = $nights;

                        $guest->items->each(function ($item) use (&$payment, $nights, &$nightsLeft) {
                            $item->each(function ($item) use (&$payment, $nights, &$nightsLeft) {
                                if($nightsLeft > 0) {
                                    if ($item->quantity > $nightsLeft) {
                                        $payment += $item->rate * $nightsLeft;
                                    } else {
                                        $payment += $item->rate * $item->quantity;
                                    }

                                    $nightsLeft -= $item->quantity;
                                }
                            });
                        });
                    });
                });

                $payment += $insurance;
            }

            $this->invoiceAttributes['minimum_deposit'] = $payment;
        }
    
        return $this->invoiceAttributes['minimum_deposit'];
    }    

    public function getMinimumPayment($client) {
        $payment = 0;
    
        if (!$client->guests->count()) {
            return $payment;
        }

        if (!$this->group || ($this->group && $this->group->is_fit)) {
            $insurance = $client->fitRate ? $client->fitRate->insurance : 0;

            if ($this->deposit && $this->deposit_type) {
                $deposit = $this->deposit;
                $deposit_type = $this->deposit_type;
            } else if ($this->group) {
                $deposit = $this->group->deposit;
                $deposit_type = $this->group->deposit_type;
            } else {
                $deposit = 0;
                $deposit_type = 'fixed';
            }
        } else {
            $insurance = $this->getInsuranceRates($client->guests)->reduce(function ($insuranceTotal, $insurance) {
                return $insuranceTotal + ($insurance->rate * $insurance->quantity);
            }, 0);

            $deposit = $this->group->deposit;
            $deposit_type = $this->group->deposit_type;
        }

        if ($deposit_type == 'fixed') {
            $payment += $deposit;
            $payment += $insurance;
        } else if ($deposit_type == 'per person') {
            $payment += ($deposit * $client->guests->count());
            $payment += $insurance;
        } else if ($deposit_type == 'percentage') { 
            $client_total = $this->getClientTotal($client) - $insurance;
            $payment += $client_total * ($deposit / 100);
            $payment += $insurance;
        } else if ($deposit_type == 'nights' && $this->group && !$this->group->is_fit) {
            $guests = $this->breakdownGuests($client->guests);
            $nights = $deposit;

            $guests->each(function ($guest) use (&$payment, $nights) {
                $nightsLeft = $nights;

                $guest->items->each(function ($item) use (&$payment, $nights, &$nightsLeft) {
                    $item->each(function ($item) use (&$payment, $nights, &$nightsLeft) {
                        if($nightsLeft > 0) {
                            if ($item->quantity > $nightsLeft) {
                                $payment += $item->rate * $nightsLeft;
                            } else {
                                $payment += $item->rate * $item->quantity;
                            }

                            $nightsLeft -= $item->quantity;
                        }
                    });
                });
            });

            $payment += $insurance;
        }

        return $payment;
    }

    public function breakdownClients($clients) {
        return $clients->map(function ($client) {
            $guests = $this->breakdownGuests($client->guests);
            
            if (!$this->group || ($this->group && $this->group->is_fit)) {
                $accommodation = $client->fitRate ? $client->fitRate->accommodation : 0;
                $insurance = $client->fitRate ? $client->fitRate->insurance : 0;
            } else {
                $accommodation = null;
                $insurance = $this->getInsuranceRates($client->guests);
            }

            $extras = $this->breakdownExtras($client->extras);

            if (!$this->group || ($this->group && $this->group->is_fit)) {
                $total = $guests->reduce(function ($total, $guest) {
                        return $total + $guest->total;
                    }, 0) +
                    $accommodation +
                    $insurance +
                    ($extras->reduce(function ($total, $extra) {
                        return $total + ($extra->price * $extra->quantity);
                    }, 0));
            } else {
                $total = $guests->reduce(function ($total, $guest) {
                        return $total + $guest->total;
                    }, 0) +
                    ($insurance->reduce(function ($insuranceTotal, $insurance) {
                        return $insuranceTotal + ($insurance->rate * $insurance->quantity);
                    }, 0)) +
                    ($extras->reduce(function ($total, $extra) {
                        return $total + ($extra->price * $extra->quantity);
                    }, 0));
            }

            return (object)[
                'id' => $client->id,
                'name' => $client->name,
                'reservation_code' => $client->reservation_code,
                'address' => (object)[
                    'line_1' => is_null($client->card) ? '' : ($client->card->address->line_1 . (is_null($client->card->address->line_2) ? '' : ', ' . $client->card->address->line_2)),
                    'line_2' => is_null($client->card) ? '' : (
                                $client->card->address->city .
                                ', ' .
                                ($client->card->address->state_abbreviation ?? $client->card->address->state_name) .
                                ' ' .
                                $client->card->address->zip_code .
                                ($client->card->address->country_name == 'United States Of America' ? '' : ', ' . $client->card->address->country_name)
                            )
                    ],
                'guests' => $guests,
                'accommodation' => $accommodation,
                'insurance' => $insurance,
                'extras' => $extras,
                'total' => $total,
                'paymentsWithPending' => $client->payments->reduce(function ($total, $payment) {
                        return $total + (is_null($payment->cancelled_at) ? $payment->amount : 0);
                    }, 0),
                'payments' => $client->payments->reduce(function ($total, $payment) {
                        return $total + (is_null($payment->confirmed_at) ? 0 : $payment->amount);
                    }, 0),
                'payment_details' => $client->payments->whereNotNull('confirmed_at'),
                'acceptedFitQuote' => $client->acceptedFitQuote ? true : false,
            ];
        });
    }

    private function breakdownGuests($guests) {
        $roundTripTransportedGuests = $guests->filter(function ($guest) {
            return in_array($guest->transportation_type, [null, self::TRANSPORTATION_TYPE_ROUND_TRIP]);
        });

        $isSingle = $roundTripTransportedGuests->count() == 1;

        return $guests->map(function ($guest) use ($isSingle) {
            $groupAirport = null;
            $transportationTotal = 0;

            if ($this->group) {
                $groupAirport = $guest->customGroupAirport() ?: $this->group->defaultAirport();

                if ($this->group->transportation && $guest->transportation) {
                    if ($guest->transportation_type > 1) {
                        $transportationTotal = number_format($groupAirport->one_way_transportation_rate, 2);
                    } else {
                        if ($isSingle) {
                            $transportationTotal = number_format($groupAirport->single_transportation_rate, 2);
                        } else {
                            $transportationTotal = number_format($groupAirport->transportation_rate, 2);
                        }
                    }
                }
            }

            if (!$this->group || ($this->group && $this->group->is_fit)) {
                $itemsByRoomBlock = null;
                $total = $transportationTotal;
                $insuranceRate = null;
            } else {
                $itemsByRoomBlock = $this->getItems($guest);

                $total = $itemsByRoomBlock->reduce(function ($total, $roomBlockItems) {
                    return $total + $roomBlockItems->reduce(function ($subTotal, $item) {
                        return $subTotal + ($item->rate * $item->quantity);
                    }, 0);
                }, 0) + $transportationTotal;

                $insuranceRate = $this->getGuestInsuranceRate($guest);

                if ($guest->insurance) {
                    $category = $insuranceRate->from . (is_null($insuranceRate->to) ? '+' : '-' . $insuranceRate->to);
                } else if (is_null($guest->insurance)) {
                    $category = 'Pending';
                } else {
                    $category = 'Declined';
                }

                $insuranceRate->category = $category;
            }

            return (object)[
                'id' => $guest->id,
                'name' => $guest->name,
                'groupAirport' => $groupAirport, 
                'transportation' => (bool) $guest->transportation,
                'transportation_type' => $guest->transportation_type,
                'is_single' => $isSingle,
                'check_in' => isset($guest->check_in) ? $guest->check_in : null,
                'check_out' => isset($guest->check_out) ? $guest->check_out : null,
                'items' => $itemsByRoomBlock,
                'total' => $total,
                'transportationTotal' => $transportationTotal,
                'insuranceRate' => $insuranceRate,
            ];
        });
    }

    public function getItems($guest) {
        $items = collect();

        $this->roomBlocks->each(function ($roomBlock) use ($guest, $items) {
            $roomBlockNights = $this->nights->where('room_block_id', $roomBlock->id);
            $roomBlockItems = collect();
            $childRates = $roomBlock->child_rates;

            foreach ($roomBlockNights as $roomBlockNight) {
                $roomBlockNight->nights->each(function ($night) use ($guest, $roomBlock, $roomBlockItems, &$childRates) {
                    $item = false;

                    if ($night->adults->contains('id', $guest->id)) {
                        $category = $this->buildItemCategoryString('adult', $night->date, $night->adults->count(), $roomBlock);

                        if (is_null($item = $roomBlockItems->get($category))) {
                            $roomBlockItems->put($category, (object)[
                                'rate' => ($roomBlock->rates->firstWhere('occupancy', $night->adults->count()) ?? $roomBlock->rates->sortBy('occupancy')->last())[(!is_null($roomBlock->split_date) && $night->date->isAfter($roomBlock->split_date)) ? 'split_rate' : 'rate'],
                            ]);
                        }
                    } else if ($night->children->contains('id', $guest->id)) {
                        $category = $this->buildItemCategoryString('child', $night->date, $guest, $roomBlock);

                        if (is_null($item = $roomBlockItems->get($category))) {
                            $guestAge = $guest->birth_date->diffInYears($night->date);

                            $childRate = $childRates->first(function ($childRate) use ($guestAge) {
                                return $guestAge >= $childRate->from && $guestAge <= $childRate->to;
                            });

                            $roomBlockItems->put($category, (object)[
                                'rate' => (!is_null($roomBlock->split_date) && $night->date->isAfter($roomBlock->split_date)) ?
                                    $childRate->split_rate :
                                    $childRate->rate
                            ]);

                            $filteredChildRates = $childRates->filter(function ($childRate) use ($guestAge) {
                                return $guestAge >= $childRate->from && $guestAge <= $childRate->to;
                            });

                            if($filteredChildRates->count() > 1) {
                                $alreadyUsedChildRate = $filteredChildRates->first();

                                $childRates = $childRates->filter(function ($childRate) use ($alreadyUsedChildRate) {
                                    return $childRate->uuid !== $alreadyUsedChildRate->uuid;
                                });
                            }
                        }
                    }

                    if ($item !== false) {
                        if (is_null($item)) {
                            $item = $roomBlockItems->last();
                            $item->quantity = 1;

                            $item->dates = [
                                (object)[
                                    'start' => $night->date->copy()->subDay(),
                                    'end' => $night->date->copy()
                                ]
                            ];
                        } else {
                            $item->quantity++;
                            $latestDateRange = end($item->dates);

                            if ($latestDateRange->end->diffInDays($night->date) === 1) {
                                $latestDateRange->end = $night->date->copy();
                            } else {
                                array_push($item->dates, (object)[
                                    'start' => $night->date->copy()->subDay(),
                                    'end' => $night->date->copy()
                                ]);
                            }
                        }
                    }
                });
            }

            if (count($roomBlockItems) > 0) {
                $items->put($roomBlock->id, $roomBlockItems);
            }
        });

        return $items;
    }

    private function buildItemCategoryString($type, $date, $data, $roomBlock) {
        $category = '';

        if ($type == 'adult') {
            $category .= $this->occupantsText[$data];
        } else if ($type == 'child') {
            $childAge = $data->birth_date->diffInYears($date);

            $childRate = $roomBlock->child_rates->first(function ($childRate) use ($childAge) {
                return $childAge >= $childRate->from && $childAge <= $childRate->to;
            });

            if ($childRate) {
                $category .= 'CHD ' . $childRate->from . '-' . $childRate->to;
            } else {
                $category .= 'CHD (Unknown Rate)';
            }
        }

        if (!is_null($roomBlock->split_date) && $date->isAfter($roomBlock->split_date)) {
            $category .= ' (After ' . $roomBlock->split_date->format('m/d') . ')';
        }

        return $category;
    }

    public function getInsuranceRates($guests, $force = false) {
        $insurance = $this->group->getInsuranceRate($this->created_at);
        $rates = collect();

        $guests->each(function ($guest) use ($force, $insurance, $rates) {
            $guestRate = (object) [
                'from' => 0,
                'to' => null,
                'rate' => 0
            ];

            $last_check_out = $this->roomBlocks()->orderBy('check_out', 'desc')->first() ? $this->roomBlocks()->orderBy('check_out', 'desc')->first()->pivot->check_out->format('Y-m-d') : '';

            if ($force || $guest->insurance) {

                $roomBlockTotals = $this->getItems($guest)->map(function ($roomBlockItems, $roomBlockId) {
                    return $roomBlockItems->reduce(function ($total, $item) {
                        return $total + ($item->rate * $item->quantity);
                    }, 0);
                });

                $guestTotal = $insurance->type == 'total' ? $roomBlockTotals->sum() : $guest->check_in->diffInDays($last_check_out);

                foreach($insurance->rates as $rate) {
                    $guestRate->rate = $rate['rate'];

                    if ($rate['to'] > $guestTotal) {
                        $guestRate->to = $rate['to'];

                        break;
                    }

                    $guestRate->from = $rate['to'];
                }

                $category = $guestRate->from . (is_null($guestRate->to) ? '+' : '-' . $guestRate->to);
            } else if (is_null($guest->insurance)) {
                $category = 'Pending';
            } else {
                $category = 'Declined';
            }

            if (is_null($rate = $rates->get($category))) {
                $rates->put($category, (object) [
                    'rate' => is_null($guestRate) ? 0 : $guestRate->rate,
                    'quantity' => 1
                ]);
            } else {
                $rate->quantity++;
            }
        });

        return $rates;
    }

    public function getGuestInsuranceRate($guest) {
        $insurance = $this->group->getInsuranceRate($this->created_at);

        $guestRate = (object) [
            'from' => 0,
            'to' => null,
            'rate' => 0
        ];

        if (!$guest->insurance) {
            return $guestRate;
        }

        $roomBlockTotals = $this->getItems($guest)->map(function ($roomBlockItems, $roomBlockId) {
            return $roomBlockItems->reduce(function ($total, $item) {
                return $total + ($item->rate * $item->quantity);
            }, 0);
        });

        $last_check_out = $this->roomBlocks()->orderBy('check_out', 'desc')->first() ? $this->roomBlocks()->orderBy('check_out', 'desc')->first()->pivot->check_out->format('Y-m-d') : '';
        $guestTotal = $insurance->type == 'total' ? $roomBlockTotals->sum() : $guest->check_in->diffInDays($last_check_out);

        foreach($insurance->rates as $rate) {
            $guestRate->rate = $rate['rate'];

            if ($rate['to'] > $guestTotal) {
                $guestRate->to = $rate['to'];

                break;
            }

            $guestRate->from = $rate['to'];
        }

        return $guestRate;
    }

    private function breakdownExtras($extras) {
        return $extras->map(function ($extra) {
            return (object)[
                'description' => $extra->description,
                'price' => $extra->price,
                'quantity' => $extra->quantity
            ];
        });
    }

    public function getDueDatesAttribute() {
        if (!isset($this->invoiceAttributes['due_dates'])) {
            if ($this->group) {
                $dueDates = collect([
                    'final cancellation date' => $this->group->cancellation_date,
                    'balance due date' => $this->group->balance_due_date,
                ]);

                $this->group->due_dates->each(function ($dueDate) use (&$dueDates) {
                    $dueDates->put($dueDate->key, $dueDate->date);
                });
            } else {
                $dueDates = collect([
                    'final cancellation date' => $this->cancellation_date,
                    'balance due date' => $this->balance_due_date,
                ]);

                $this->bookingDueDates->each(function ($dueDate) use (&$dueDates) {
                    $dueDates->put($dueDate->key, $dueDate->date);
                });
            }

            $this->invoiceAttributes['due_dates'] = $dueDates;
        }

        return $this->invoiceAttributes['due_dates'];
    }

    public static function isRoomBlockAvailable($room_block, $filters) {
        $room = $room_block->room;

        if (!empty($room->min_occupants) && ($room->min_occupants > ((int) $filters['adults'] + (int) $filters['children']))) return false;
        if (!empty($room->max_occupants) && ($room->max_occupants < ((int) $filters['adults'] + (int) $filters['children']))) return false;
        if (!empty($room->adults_only) && ($room->adults_only && ((int) $filters['children']) > 0)) return false;
        if (!empty($room->max_adults) && ($room->max_adults < ((int) $filters['adults']))) return false;
        if (!empty($room->max_children) && ($room->max_children < ((int) $filters['children']))) return false;

        return true;
    }

    public static function createBookingPreview($group, $room_block, $filters) {
        $faker = \Faker\Factory::create();

        $booking = self::make([
            'group_id' => $group->id,
        ]);

        $booking->room_block_id = $room_block->id;
        $booking->check_in = $filters['checkIn'];
        $booking->check_out = $filters['checkOut'];
        
        $booking->setRelation('roomBlocks', new \Illuminate\Database\Eloquent\Collection([$room_block]));

        $room_block->pivot = new Pivot([
            'bed' => 'N/A',
            'check_in' => $filters['checkIn'],
            'check_out' => $filters['checkOut'],
        ], $booking);

        $bookingClient = \App\Models\BookingClient::make([
            'first_name' => $faker->firstName,
            'last_name' => $faker->lastName
        ]);

        $bookingClientGuests = [];
        $bookingGuests = [];
        $guestCount = 0;
        $adultsCount = 0;

        while($adultsCount < (int) $filters['adults']) {
            $guestCount++;

            $bookingClientGuest = \App\Models\Guest::make([
                'birth_date' => $faker->dateTimeBetween('-40 year', '-20 year'),
                'check_in' => $filters['checkIn'],
                'check_out' => $filters['checkOut'],
                'insurance' => null,
            ]);

            $bookingClientGuest->id = $guestCount;

            array_push($bookingClientGuests, $bookingClientGuest);
            array_push($bookingGuests, $bookingClientGuest);

            $adultsCount++;
        }

        foreach ($filters['birthDates'] as $index => $birthDate) {
            $guestCount++;

            $bookingClientGuest = \App\Models\Guest::make([
                'birth_date' => $birthDate,
                'check_in' => $filters['checkIn'],
                'check_out' => $filters['checkOut'],
                'insurance' => null,
            ]);

            $bookingClientGuest->id = $guestCount;

            array_push($bookingClientGuests, $bookingClientGuest);
            array_push($bookingGuests, $bookingClientGuest);
        }

        $bookingClient->setRelation('guests', new \Illuminate\Database\Eloquent\Collection($bookingClientGuests));

        $booking->setRelation('clients', new \Illuminate\Database\Eloquent\Collection(collect([$bookingClient])));
        $booking->setRelation('guests', new \Illuminate\Database\Eloquent\Collection($bookingGuests));

        return $booking;
    }
}

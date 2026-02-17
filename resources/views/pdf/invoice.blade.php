@extends('pdf.layout')

@section('title')
    Invoice | Barefoot Bridal
@endsection

@section('styles')
    <style>
        .client-page  {
            /* page-break-inside: avoid; */
            position: relative;
            top: 0;
            left: 0;
            margin: 0;
            border: 24px #e5dcdf solid;
            padding: 24px 48px;
            width: 912px;
            /* height: 1398px; */
        }

        .tc-page  {
            page-break-inside: avoid;
            position: relative;
            top: 0;
            left: 0;
            margin: 0;
            border: 24px #e5dcdf solid;
            padding: 24px 48px;
            width: 912px;
            height: 1398px;
        }

        .client-page .header {
            font-size: 144px;
            letter-spacing: 52px;
            text-align: center;
        }

        .client-info table td {
            padding-bottom: 0;
        }

        .client-info table th,
        .client-info table td {
            border: none;
            background-color: transparent;
            text-align: left;
        }

        .client-info table th:last-child,
        .client-info table td:last-child {
            text-align: right;
        }

        .booking-details {
            padding-top: 32px;
        }

        table tr.is-total {
            border-bottom: 1px solid #ffffff;
        }

        table tr.is-total td {
            height: 32px;
        }

        table tr.is-total td:first-child {
            text-align: left;
        }

        table tr.is-total td:last-child {
            background-color: #e5dcdf;
        }

        .note {
            margin-top: 32px;
            font-size: 14px;
            font-style: italic;
            text-align: center;
        }

        .contact-info {
            margin-top: 32px;
        }

        .contact-info table th,
        .contact-info table td {
            border: none;
            background-color: transparent;
        }

        .contact-info table td > div {
            padding: 4px 4px 0;
        }

        .contact-info table td > div .due-date {
            text-decoration: underline;
        }

        .tc-page .header {
            font-size: 60px;
            letter-spacing: 13.5px;
            text-align: center;
        }

        .tc-page .body {
            font-size: 14px;
            text-align: justify;
        }

        .tc-page .body > div {
            margin-bottom: 16px;
        }

        .client-page .subheader {
            font-size: 20px;
            text-align: center;
            margin-bottom: 32px;
            text-transform: none;
        }
    </style>
@endsection

@section('body')
    @foreach($invoice->clients as $pageCount => $client)
        <div class="client-page page">
            <div class="header">
                @if ($invoice->details->group)
                    {{ ($invoice->details->group->is_fit && !$client->acceptedFitQuote) ? 'Quote' : 'Invoice' }}
                @else
                    {{ !$client->acceptedFitQuote  ? 'Quote' : 'Invoice' }}
                @endif
            </div>
            @if(isset($hasChanges) && $hasChanges)
                <div class="subheader">
                    <b>Changes have not yet been confirmed.</b>
                </div>
            @endif
            <div style="margin-bottom:70px" class="body">
                <div class="client-info">
                    <table>
                        <tr>
                            <th>{{ $invoice->details->group ? 'Wedding Group' : 'Reservation' }}</th>
                            <th>Bill To</th>
                        </tr>
                        <tr>
                            <td>{{ $invoice->details->group ? $invoice->details->group->name : $invoice->details->booking->full_name }}</td>
                            <td>{{ $client->name }}</td>
                        </tr>
                        <tr>
                            <td>{{ $invoice->details->group ? $invoice->details->group->event_date->format('F jS, Y') : $invoice->details->booking->check_in->format('F jS, Y') . ' - ' . $invoice->details->booking->check_out->format('F jS, Y') }}</td>
                            <td>{{ $client->address->line_1 }}</td>
                        </tr>
                        <tr>
                            <td>Reservation Code: {{ $client->reservation_code }}</td>
                            <td>{{ $client->address->line_2 }}</td>
                        </tr>
                    </table>
                </div>

                <div class="booking-details">
                    <table>
                        <tr>
                            <th>Resort</th>
                            @if(!$invoice->details->group || ($invoice->details->group && $invoice->details->group->is_fit)) <th>Room</th> @endif
                            <th>Booking Dates</th>
                            <th>Bedding Request</th>
                            <th @if($invoice->details->group && !$invoice->details->group->is_fit) colspan="2" @endif>Supplier</th>
                            <th>{{ $invoice->details->group ? 'Group ID' : 'Reservation ID' }}</th>
                        </tr>
                        @foreach ($invoice->details->rooms as $room)
                            <tr>
                                <td>{{ $room->hotel }}</td>
                                @if(!$invoice->details->group || ($invoice->details->group && $invoice->details->group->is_fit)) <td>{{$room->room}}</td> @endif
                                <td class="no-wrap">
                                    {{ $room->travel_dates->check_in->format('M d') }} -
                                    {{ $room->travel_dates->check_out->format($room->travel_dates->check_in->month != $room->travel_dates->check_out->month ? 'M d, Y' : 'd, Y') }}
                                </td>
                                <td>{{ $room->bedding }}</td>
                                @if ($loop->first)
                                    <td rowspan="{{ $invoice->details->rooms->count() }}" @if($invoice->details->group && !$invoice->details->group->is_fit) colspan="2" @endif>{{ $invoice->details->group ? $invoice->details->group->provider->name : ($invoice->details->booking->provider ? $invoice->details->booking->provider->name : '') }}</td>
                                    <td rowspan="{{ $invoice->details->rooms->count() }}" >{{ $invoice->details->group ? $invoice->details->group->id_at_provider : $invoice->details->booking->id_at_provider }}</td>
                                @endif
                            </tr>
                        @endforeach

                        <tr><td colspan="6" class="is-spaced is-borderless"></td></tr>

                        @if (!$invoice->details->group || ($invoice->details->group && $invoice->details->group->is_fit))
                            @if ($client->guests->count())
                                <tr>
                                    <th colspan="3">Guest Names</th>
                                    <th colspan="3">Travel Dates</th>
                                </tr>
                                @foreach ($client->guests as $guest)
                                    <tr>
                                        <td colspan="3">{{ $guest->name }}</td>
                                        <td colspan="3">{{ $guest->check_in->format('M d') }} - {{ $guest->check_out->format($guest->check_in->month != $guest->check_out->month ? 'M d, Y' : 'd, Y') }}</td>
                                    </tr>
                                @endforeach

                                <tr><td colspan="6" class="is-spaced is-borderless"></td></tr>
                            @endif

                            <tr>
                                <th colspan="3">Item</th>
                                <th>Price</th>
                                <th>Quantity</th>
                                <th>Subtotal</th>
                            </tr>

                            <tr>
                                <td colspan="3">{{ $invoice->details->group ? 'Accommodation' : 'Accommodation & Travel' }}</td>
                                <td>${{ number_format($client->accommodation, 2) }}</td>
                                <td>1</td>
                                <td>${{ number_format($client->accommodation, 2) }}</td>
                            </tr>

                            @if ($invoice->details->group)
                                @foreach ($client->guests as $guest)
                                    @if ($guest->transportation)
                                        <tr>
                                            <td colspan="3">
                                                @if ($guest->transportation_type > 1)
                                                    {{ $guest->name }} - One Way Airport Transfers ({{ $guest->groupAirport->airport->airport_code }}) : {{ $invoice->one_way_transportation->category }}
                                                @else
                                                    {{ $guest->name }} - Round Trip Airport Transfers ({{ $guest->groupAirport->airport->airport_code }}) : {{ $invoice->round_trip_transportation->category }}
                                                @endif
                                            </td>
                                            <td>
                                                @if ($guest->transportation_type > 1)
                                                    ${{ number_format($guest->groupAirport->one_way_transportation_rate, 2) }}
                                                @else
                                                    @if ($guest->is_single)
                                                        ${{ number_format($guest->groupAirport->single_transportation_rate, 2) }}
                                                    @else
                                                        ${{ number_format($guest->groupAirport->transportation_rate, 2) }}
                                                    @endif
                                                @endif
                                            </td>
                                            <td>1</td>
                                            <td>
                                                @if ($guest->transportation_type > 1)
                                                    ${{ number_format($guest->groupAirport->one_way_transportation_rate, 2) }}
                                                @else
                                                    @if ($guest->is_single)
                                                        ${{ number_format($guest->groupAirport->single_transportation_rate, 2) }}
                                                    @else
                                                        ${{ number_format($guest->groupAirport->transportation_rate, 2) }}
                                                    @endif
                                                @endif
                                            </td>
                                        </tr>
                                    @endif
                                @endforeach
                            @endif
                                 
                            <tr>
                                <td colspan="3">
                                    NON REFUNDABLE TRAVEL INSURANCE
                                    @if($client->insurance == 0)
                                        : DECLINED
                                    @endif
                                </td>
                                <td>${{ number_format($client->insurance, 2) }}</td>
                                <td>1</td>
                                <td>${{ number_format($client->insurance, 2) }}</td>
                            </tr>

                            @foreach ($client->extras as $extra)
                                <tr>
                                    <td colspan="3">{{ $extra->description }}</td>
                                    <td>${{ number_format($extra->price, 2) }}</td>
                                    <td>{{ $extra->quantity }}</td>
                                    <td>${{ number_format(($extra->price * $extra->quantity), 2) }}</td>
                                </tr>
                            @endforeach

                            @if ($pageCount == 0 && $invoice->details->booking->special_requests)
                                <tr class="special-requests">
                                    <th style="font-size: 12px;">Special Requests</th>
                                    <td colspan="5" style="text-align: left; font-size: 12px;">{{ $invoice->details->booking->special_requests }}</td>
                                </tr>
                            @endif
                        @else
                            @if ($client->guests->count() || $client->extras->count())
                                <tr>
                                    <th>Guest Name</th>
                                    <th>Travel Dates</th>
                                    <th>Room</th>
                                    <th>Price / Night</th>
                                    <th>Nights</th>
                                    <th>Subtotal</th>
                                </tr>

                                @foreach ($client->guests as $guest)
                                    @php
                                        $first = true;
                                        $items_count = collect($guest->items)->sum(fn($items) => count($items));
                                    @endphp
                                    @foreach ($guest->items as $room_block_id => $items)
                                        @php
                                            $room_block = $invoice->details->rooms->where('id', $room_block_id)->first();
                                        @endphp
                                        @if ($guest->transportation == 1)
                                            <tr>
                                                @if ($first)
                                                    <td rowspan="{{ $items_count + 2 }}">{{ $guest->name }}</td>
                                                    <td rowspan="{{ $items_count + 2 }}">
                                                        {{ $guest->check_in->format('M d') }} - {{ $guest->check_out->format($guest->check_in->month != $guest->check_out->month ? 'M d, Y' : 'd, Y') }}
                                                    </td>
                                                @endif
                                                <td rowspan="{{ $items->count() }}">{{ $room_block->hotel . ' - ' . $room_block->room }}</td>
                                                @foreach ($items as $category => $item)
                                                    @if ($loop->iteration > 1) <tr> @endif
                                                        <td>${{ number_format($item->rate, 2) . ' - ' . $category }}</td>
                                                        <td>{{ $item->quantity }}</td>
                                                        <td>${{ number_format($item->rate * $item->quantity, 2) }}</td>
                                                    </tr>
                                                @endforeach

                                            @if ($guest->items->count() == $loop->iteration)
                                                <tr>
                                                    <td>
                                                        @if ($guest->transportation_type > 1)
                                                            One Way Airport Transfers ({{ $guest->groupAirport->airport->airport_code }}) : {{ $invoice->one_way_transportation->category }}</td>
                                                        @else
                                                            Round Trip Airport Transfers ({{ $guest->groupAirport->airport->airport_code }}) : {{ $invoice->round_trip_transportation->category }}</td>
                                                        @endif
                                                    <td>
                                                        @if ($guest->transportation_type > 1)
                                                            ${{ number_format($guest->groupAirport->one_way_transportation_rate, 2) }}
                                                        @else
                                                            @if ($guest->is_single)
                                                                ${{ number_format($guest->groupAirport->single_transportation_rate, 2) }}
                                                            @else
                                                                ${{ number_format($guest->groupAirport->transportation_rate, 2) }}
                                                            @endif
                                                        @endif
                                                    </td>
                                                    <td>1</td>
                                                    <td>
                                                        @if ($guest->transportation_type > 1)
                                                            ${{ number_format($guest->groupAirport->one_way_transportation_rate, 2) }}
                                                        @else
                                                            @if ($guest->is_single)
                                                                ${{ number_format($guest->groupAirport->single_transportation_rate, 2) }}
                                                            @else
                                                                ${{ number_format($guest->groupAirport->transportation_rate, 2) }}
                                                            @endif
                                                        @endif
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>Non Refundable Travel Insurance : {{ $guest->insuranceRate->category }}</td>
                                                    <td>${{ number_format($guest->insuranceRate->rate, 2) }}</td>
                                                    <td>1</td>
                                                    <td>${{ number_format($guest->insuranceRate->rate, 2) }}</td>
                                                </tr>
                                            @endif
                                        @else
                                            <tr>
                                                @if ($first)
                                                    <td rowspan="{{ $items_count + 1 }}">{{ $guest->name }}</td>
                                                    <td rowspan="{{ $items_count + 1 }}">
                                                        {{ $guest->check_in->format('M d') }} - {{ $guest->check_out->format($guest->check_in->month != $guest->check_out->month ? 'M d, Y' : 'd, Y') }}
                                                    </td>
                                                @endif
                                                <td rowspan="{{ $items->count() }}">{{ $room_block->hotel . ' - ' . $room_block->room }}</td>
                                                @foreach ($items as $category => $item)
                                                    @if ($loop->iteration > 1) <tr> @endif
                                                        <td>${{ number_format($item->rate, 2) . ' - ' . $category }}</td>
                                                        <td>{{ $item->quantity }}</td>
                                                        <td>${{ number_format($item->rate * $item->quantity, 2) }}</td>
                                                    </tr>
                                                @endforeach
                                            
                                            @if ($guest->items->count() == $loop->iteration)
                                                <tr>
                                                    <td>Non Refundable Travel Insurance : {{ $guest->insuranceRate->category }}</td>
                                                    <td>${{ number_format($guest->insuranceRate->rate, 2) }}</td>
                                                    <td>1</td>
                                                    <td>${{ number_format($guest->insuranceRate->rate, 2) }}</td>
                                                </tr>
                                            @endif
                                        @endif
                                        @php
                                            $first = false;
                                        @endphp
                                    @endforeach
                                @endforeach

                                @foreach ($client->extras as $extra)
                                    <tr>
                                        <td></td>
                                        <td></td>
                                        <td>{{ $extra->description }}</td>
                                        <td>${{ number_format($extra->price, 2) }}</td>
                                        <td>{{ $extra->quantity }}</td>
                                        <td>${{ number_format(($extra->price * $extra->quantity), 2) }}</td>
                                    </tr>
                                @endforeach

                                @if ($pageCount == 0 && $invoice->details->booking->special_requests)
                                    <tr class="special-requests">
                                        <th style="font-size: 12px;">Special Requests</th>
                                        <td colspan="5" style="text-align: left; font-size: 12px;">{{ $invoice->details->booking->special_requests }}</td>
                                    </tr>
                                @endif
                            @endif
                        @endif

                        <tr class="is-total">
                            <td class="is-borderless" colspan="2"></td>
                            <td class="is-borderless">Total</td>
                            <td class="is-borderless" colspan="2"></td>
                            <td class="is-borderless">${{ number_format($client->total, 2) }}</td>
                        </tr>

                        @foreach($client->payment_details as $payment_detail)
                            <tr>
                                <td class="is-borderless" colspan="2"></td>
                                <td class="is-borderless">@if($loop->first) Payments @endif</td>
                                <td class="is-borderless" colspan="2">{{ $payment_detail->confirmed_at->format('m-d-Y') }}</td>
                                <td class="is-borderless">${{ number_format($payment_detail->amount, 2) }}</td>
                            </tr>
                        @endforeach

                        <tr class="is-total">
                            <td class="is-borderless" colspan="2"></td>
                            <td class="is-borderless">Total Amount Paid</td>
                            <td class="is-borderless" colspan="2"></td>
                            <td class="is-borderless">${{ number_format($client->payments, 2) }}</td>
                        </tr>

                        <tr class="is-total">
                            <td class="is-borderless" colspan="2"></td>
                            <td class="is-borderless">Balance</td>
                            <td class="is-borderless" colspan="2"></td>
                            <td class="is-borderless">${{ number_format($client->total - $client->payments, 2) }}</td>
                        </tr>
                    </table>
                </div>

                <div class="note">
                    Double check all information on this invoice and let us know of any errors within 24 hours of receipt!
                    <br>
                    Travel documents will be emailed to the email on your booking form. Please update us with any changes.
                    <br>
                    Please note, bedding is a special request and is subject to availability.
                </div>

                <div class="contact-info">
                    <table>
                        <tr>
                            <th>Due Dates</th>
                            <th>Barefoot Bridal</th>
                        </tr>
                        <tr>
                            <td>
                                @foreach ($invoice->details->due_dates->sort() as $key => $date)
                                    @if ($date)
                                        <div>{{ $key }}: <span class="due-date">{{ $date->format('F j, Y') }}</span></div>
                                    @endif
                                @endforeach
                            </td>
                            <td>
                                <div>www.barefootbridal.com</div>
                                <div>{{ config('emails.groups') }}</div>
                                <div>866-8-BAREFOOT</div>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <div class="footer">
                <img src="{{ ($path = realpath(public_path('img/footer-logo.png'))) ? 'file://'.$path : asset('img/footer-logo.png') }}">
                <div class="paging">Page {{ $pageCount + 1 }} of {{ $invoice->clients->count() + 1 }}</div>
            </div>
        </div>
    @endforeach
    <div class="tc-page page">
        <div class="header">
            Terms & Conditions
        </div>

        <div class="body">
            @if ($invoice->details->group)
                @if ($invoice->details->group->terms_and_conditions)
                    {!! $invoice->details->group->terms_and_conditions !!}
                @else
                    @if ($invoice->details->group->is_fit)
                        @include('pdf.static.fitTermsConditions', ['group' => $invoice->details->group])
                    @else
                        @include('pdf.static.termsConditions', ['group' => $invoice->details->group])
                    @endif
                @endif
            @else
                @if ($invoice->details->booking->terms_and_conditions)
                    {!! $invoice->details->booking->terms_and_conditions !!}
                @else
                    @include('pdf.static.individualBookingTermsConditions', ['cancellation_date' => $invoice->details->booking->cancellation_date ? $invoice->details->booking->cancellation_date->format('F d, Y') : null])
                @endif
            @endif
        </div>

        <div class="footer">
            <img src="{{ ($path = realpath(public_path('img/footer-logo.png'))) ? 'file://'.$path : asset('img/footer-logo.png') }}">
            <div class="paging">Page {{ $invoice->clients->count() + 1 }} of {{ $invoice->clients->count() + 1 }}</div>
        </div>
    </div>
@endsection

<?php

namespace App\Exports;

use App\Models\Group;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class BookingSheet implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithColumnFormatting, WithStyles, WithEvents, WithTitle
{
    private $group;
    private $rowCount = 1;
    private $bookingRows;
    private $clientRows;
    private $guestRows;

    const EMPTY_CELL = null;

    public function __construct(Group $group)
    {
        $this->group = $group;
        $this->bookingRows = collect();
        $this->clientRows = collect();
        $this->guestRows = collect();
    }


    public function title(): string
    {
        return 'Bookings';
    }

    public function collection()
    {
        return $this->group->bookings()
            ->withTrashed()
            ->with([
                'clients' => function ($query) {
                    $query->with([
                        'guests',
                        'card',
                        'extras',
                        'payments'
                    ])->whereHas('guests');
                },
                'roomBlocks'
            ])
            ->ordered()
            ->get();
    }

    public function headings(): array
    {
        if ($this->group->is_fit) {
            return [
                '#',
                'Guest Name',
                'Room',
                'Bed',
                'Travel Dates',
                'Accommodation',
                'Insurance',
                'Transfers',
                'Extras',
                'Extras Total',
                'Total',
                'Payments',
                'Balance',
                'Email',
                'Credit Card',
                'Card Holder',
                'Address',
                'Special Requests',
                'Notes',
                'Booking ID',
            ];
        } else {
            return [
                '#',
                'Guest Name',
                'Room',
                'Bed',
                'Travel Dates',
                'Occupancy',
                'Nights',
                'Per Night',
                'Insurance',
                'Transfers',
                'Extras',
                'Extras Total',
                'Total',
                'Payments',
                'Balance',
                'Email',
                'Credit Card',
                'Card Holder',
                'Address',
                'Special Requests',
                'Notes',
                'Booking ID',
            ];
        }
    }

    public function map($booking): array
    {
        $rows = [];
        $bookingRow = ['start' => $this->rowCount + 1];

        foreach ($booking->clients as $cIndex => $client) {
            $clientRow = ['start' => $this->rowCount + 1];
            $invoiceClient = $booking->invoice->clients->where('id', $client->id)->first();

            foreach ($client->guests as $gIndex => $guest) {
                $invoiceGuest = $invoiceClient->guests->where('id', $guest->id)->first();
                $guestRow = ['start' => $this->rowCount + 1];
                $firstItem = true;

                if ($this->group->is_fit) {
                    $this->rowCount++;

                    $rows[] = [
                        ($cIndex === 0 && $gIndex === 0 && $firstItem) ? $booking->order : (($cIndex && $firstItem) ? 'SP' : null),
                        $firstItem ? $guest->name : null,
                        ($cIndex === 0 && $gIndex === 0 && $firstItem) ? $booking->roomBlocks->map(fn($roomBlock) => $roomBlock->room->name)->implode(', ') : null,
                        ($cIndex === 0 && $gIndex === 0 && $firstItem) ? $booking->roomBlocks->map(fn($roomBlock) => $roomBlock->pivot->bed)->implode(', ') : null,
                        ($cIndex === 0 && $gIndex === 0 && $firstItem) ? $booking->roomBlocks->map(fn($roomBlock) => $roomBlock->pivot->check_in->format('m/d/Y') . ' - ' . $roomBlock->pivot->check_out->format('m/d/Y'))->implode(', ') : null,
                        ($gIndex === 0 && $firstItem) ? ($client->fitRate ? $client->fitRate->accommodation : null) : self::EMPTY_CELL,
                        ($gIndex === 0 && $firstItem) ? ($client->fitRate ? $client->fitRate->insurance : null) : self::EMPTY_CELL,
                        $firstItem ? $invoiceGuest->transportationTotal : null,
                        ($gIndex === 0 && $firstItem) ? $client->extras->map(fn($extra) => $extra->description . ' (' . $extra->quantity . ')')->implode(', ') : self::EMPTY_CELL,
                        ($gIndex === 0 && $firstItem) ? $client->extras->reduce(fn($total, $extra) => $total + ($extra->price * $extra->quantity), 0) : self::EMPTY_CELL,
                        ($cIndex === 0 && $gIndex === 0 && $firstItem) ? $booking->total : null,
                        ($cIndex === 0 && $gIndex === 0 && $firstItem) ? $booking->paymentTotal : null,
                        ($cIndex === 0 && $gIndex === 0 && $firstItem) ? $booking->total - $booking->paymentTotal : null,
                        ($gIndex === 0 && $firstItem) ? $client->client->email : null,
                        ($gIndex === 0 && $firstItem) ? (is_null($client->card) ? null : $client->card->number . ' ' . $client->card->expiration_date . ' ' . $client->card->code) : null,
                        ($gIndex === 0 && $firstItem) ? (is_null($client->card) ? null : $client->card->name) : null,
                        ($gIndex === 0 && $firstItem) ? (is_null($client->card) ? null : $client->card->address->fullAddress) : null,
                        ($cIndex === 0 && $gIndex === 0 && $firstItem) ? $booking->special_requests : null,
                        ($cIndex === 0 && $gIndex === 0 && $firstItem) ? $booking->notes : null,
                        ($cIndex === 0 && $gIndex === 0 && $firstItem) ? $booking->booking_id : null,
                    ];

                    $firstItem = false;
                } else {
                    foreach ($booking->getItems($guest) as $room_block_id => $items) {
                        $roomBlock = $booking->roomBlocks->where('id', $room_block_id)->first();

                        foreach ($items as $category => $item) {
                            $this->rowCount++;

                            $rows[] = [
                                ($cIndex === 0 && $gIndex === 0 && $firstItem) ? $booking->order : (($cIndex && $firstItem) ? 'SP' : null),
                                $firstItem ? $guest->name : null,
                                $roomBlock ? $roomBlock->room->name : null,
                                $roomBlock ? $roomBlock->pivot->bed : null,
                                implode(', ', array_map(fn($date) => $date->start->format('m/d/Y') . ' - ' . $date->end->format('m/d/Y'), $item->dates)),
                                $category,
                                $item->quantity,
                                $item->rate,
                                $firstItem ? ($guest->insurance ? $booking->getGuestInsuranceRate($guest)->rate : '0') : null,
                                $firstItem ? $invoiceGuest->transportationTotal : null,
                                ($gIndex === 0 && $firstItem) ? $client->extras->map(fn($extra) => $extra->description . ' (' . $extra->quantity . ')')->implode(', ') : self::EMPTY_CELL,
                                ($gIndex === 0 && $firstItem) ? $client->extras->reduce(fn($total, $extra) => $total + ($extra->price * $extra->quantity), 0) : self::EMPTY_CELL,
                                ($cIndex === 0 && $gIndex === 0 && $firstItem) ? $booking->total : null,
                                ($cIndex === 0 && $gIndex === 0 && $firstItem) ? $booking->paymentTotal : null,
                                ($cIndex === 0 && $gIndex === 0 && $firstItem) ? $booking->total - $booking->paymentTotal : null,
                                ($gIndex === 0 && $firstItem) ? $client->client->email : null,
                                ($gIndex === 0 && $firstItem) ? (is_null($client->card) ? null : $client->card->number . ' ' . $client->card->expiration_date . ' ' . $client->card->code) : null,
                                ($gIndex === 0 && $firstItem) ? (is_null($client->card) ? null : $client->card->name) : null,
                                ($gIndex === 0 && $firstItem) ? (is_null($client->card) ? null : $client->card->address->fullAddress) : null,
                                ($cIndex === 0 && $gIndex === 0 && $firstItem) ? $booking->special_requests : null,
                                ($cIndex === 0 && $gIndex === 0 && $firstItem) ? $booking->notes : null,
                                ($cIndex === 0 && $gIndex === 0 && $firstItem) ? $booking->booking_id : null,
                            ];

                            $firstItem = false;
                        }
                    }
                }

                $guestRow['end'] = $this->rowCount;
                $this->guestRows->push($guestRow);
            }

            $clientRow['end'] = $this->rowCount;
            $this->clientRows->push($clientRow);
        }

        $bookingRow['end'] = $this->rowCount;
        $bookingRow['isCancelled'] = $booking->trashed();
        $this->bookingRows->push($bookingRow);

        return $rows;
    }

    public function columnFormats(): array
    {
        if ($this->group->is_fit) {
            return [
                'F' => NumberFormat::FORMAT_CURRENCY_USD_SIMPLE,
                'G' => NumberFormat::FORMAT_CURRENCY_USD_SIMPLE,
                'H' => NumberFormat::FORMAT_CURRENCY_USD_SIMPLE,
                'J' => NumberFormat::FORMAT_CURRENCY_USD_SIMPLE,
                'K' => NumberFormat::FORMAT_CURRENCY_USD_SIMPLE,
                'L' => NumberFormat::FORMAT_CURRENCY_USD_SIMPLE,
                'M' => NumberFormat::FORMAT_CURRENCY_USD_SIMPLE,
            ];
        } else {
            return [
                'H' => NumberFormat::FORMAT_CURRENCY_USD_SIMPLE,
                'I' => NumberFormat::FORMAT_CURRENCY_USD_SIMPLE,
                'J' => NumberFormat::FORMAT_CURRENCY_USD_SIMPLE,
                'L' => NumberFormat::FORMAT_CURRENCY_USD_SIMPLE,
                'M' => NumberFormat::FORMAT_CURRENCY_USD_SIMPLE,
                'N' => NumberFormat::FORMAT_CURRENCY_USD_SIMPLE,
                'O' => NumberFormat::FORMAT_CURRENCY_USD_SIMPLE,
            ];
        }
    }

    public function styles(Worksheet $sheet)
    {
        if ($this->group->is_fit) {
            $styles = [
                'A1:T1' => ['borders' => ['bottom' => ['borderStyle' => Border::BORDER_THICK]], 'font' => ['bold' => true]],
                'A1:A' . $this->rowCount => ['borders' => ['right' => ['borderStyle' => Border::BORDER_THICK]], 'font' => ['bold' => true]],
                'T1:T' . $this->rowCount => ['borders' => ['right' => ['borderStyle' => Border::BORDER_THICK]]],
                'A1:T' . $this->rowCount => ['alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_TOP]],
                'B2:B' . $this->rowCount => ['alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT]],
                'C2:C' . $this->rowCount => ['alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT]],
                'F2:F' . $this->rowCount => ['alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT]],
                'G2:G' . $this->rowCount => ['alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT]],
                'H2:H' . $this->rowCount => ['alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT]],
                'I2:I' . $this->rowCount => ['alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'wrapText' => true]],
                'J2:J' . $this->rowCount => ['alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT]]
            ];

            $bookingRowStyles = $this->bookingRows->mapWithKeys(function($bookingRow) {
                $styles = [];

                $styles['A' . $bookingRow['start'] . ':T' . $bookingRow['end']] = [
                    'borders' => ['outline' => ['borderStyle' => Border::BORDER_MEDIUM]]
                ];

                if ($bookingRow['isCancelled']) {
                    $styles['A' . $bookingRow['start'] . ':D' . $bookingRow['end']] = ['font' => ['strikethrough' => true]];
                }

                return $styles;
            })->toArray();
        } else {
            $styles = [
                'A1:V1' => ['borders' => ['bottom' => ['borderStyle' => Border::BORDER_THICK]], 'font' => ['bold' => true]],
                'A1:A' . $this->rowCount => ['borders' => ['right' => ['borderStyle' => Border::BORDER_THICK]], 'font' => ['bold' => true]],
                'V1:V' . $this->rowCount => ['borders' => ['right' => ['borderStyle' => Border::BORDER_THICK]]],
                'A1:V' . $this->rowCount => ['alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_TOP]],
                'B2:B' . $this->rowCount => ['alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT]],
                'C2:C' . $this->rowCount => ['alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT]],
                'H2:H' . $this->rowCount => ['alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT]],
                'I2:I' . $this->rowCount => ['alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT]],
                'J2:J' . $this->rowCount => ['alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT]],
                'K2:K' . $this->rowCount => ['alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'wrapText' => true]],
                'L2:L' . $this->rowCount => ['alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT]]
            ];

            $bookingRowStyles = $this->bookingRows->mapWithKeys(function($bookingRow) {
                $styles = [];

                $styles['A' . $bookingRow['start'] . ':V' . $bookingRow['end']] = [
                    'borders' => ['outline' => ['borderStyle' => Border::BORDER_MEDIUM]]
                ];

                if ($bookingRow['isCancelled']) {
                    $styles['A' . $bookingRow['start'] . ':D' . $bookingRow['end']] = ['font' => ['strikethrough' => true]];
                }

                return $styles;
            })->toArray();
        }

        return array_merge(
            $styles,
            $bookingRowStyles
        );
    }

    public function registerEvents(): array
    {
        if ($this->group->is_fit) {
            return [
                AfterSheet::class => function(AfterSheet $event) {
                    $this->bookingRows->each(function ($bookingRow) use ($event) {
                        if ($bookingRow['start'] <= $bookingRow['end']) {
                            $event->sheet->mergeCells("C".$bookingRow['start'].":C".$bookingRow['end']);
                            $event->sheet->mergeCells("D".$bookingRow['start'].":D".$bookingRow['end']);
                            $event->sheet->mergeCells("E".$bookingRow['start'].":E".$bookingRow['end']);
                            $event->sheet->mergeCells("K".$bookingRow['start'].":K".$bookingRow['end']);
                            $event->sheet->mergeCells("L".$bookingRow['start'].":L".$bookingRow['end']);
                            $event->sheet->mergeCells("M".$bookingRow['start'].":M".$bookingRow['end']);
                            $event->sheet->mergeCells("R".$bookingRow['start'].":R".$bookingRow['end']);
                            $event->sheet->mergeCells("S".$bookingRow['start'].":S".$bookingRow['end']);
                            $event->sheet->mergeCells("T".$bookingRow['start'].":T".$bookingRow['end']);
                        }
                    });

                    $this->clientRows->each(function($clientRow) use ($event) {
                        if ($clientRow['start'] <= $clientRow['end']) {
                            $event->sheet->mergeCells("A".$clientRow['start'].":A".$clientRow['end']);
                            $event->sheet->mergeCells("F".$clientRow['start'].":F".$clientRow['end']);
                            $event->sheet->mergeCells("G".$clientRow['start'].":G".$clientRow['end']);
                            $event->sheet->mergeCells("I".$clientRow['start'].":I".$clientRow['end']);
                            $event->sheet->mergeCells("J".$clientRow['start'].":J".$clientRow['end']);
                            $event->sheet->mergeCells("N".$clientRow['start'].":N".$clientRow['end']);
                            $event->sheet->mergeCells("O".$clientRow['start'].":O".$clientRow['end']);
                            $event->sheet->mergeCells("P".$clientRow['start'].":P".$clientRow['end']);
                            $event->sheet->mergeCells("Q".$clientRow['start'].":Q".$clientRow['end']);
                        }
                    });

                    $this->guestRows->each(function($guestRow) use ($event) {
                        if ($guestRow['start'] <= $guestRow['end']) {
                            $event->sheet->mergeCells("B".$guestRow['start'].":B".$guestRow['end']);
                            $event->sheet->mergeCells("H".$guestRow['start'].":H".$guestRow['end']);
                        }
                    });
                }   
            ];
        } else {
            return [
                AfterSheet::class => function(AfterSheet $event) {
                    $this->bookingRows->each(function ($bookingRow) use ($event) {
                        if ($bookingRow['start'] <= $bookingRow['end']) {
                            $event->sheet->mergeCells("M".$bookingRow['start'].":M".$bookingRow['end']);
                            $event->sheet->mergeCells("N".$bookingRow['start'].":N".$bookingRow['end']);
                            $event->sheet->mergeCells("O".$bookingRow['start'].":O".$bookingRow['end']);
                            $event->sheet->mergeCells("T".$bookingRow['start'].":T".$bookingRow['end']);
                            $event->sheet->mergeCells("U".$bookingRow['start'].":U".$bookingRow['end']);
                            $event->sheet->mergeCells("V".$bookingRow['start'].":V".$bookingRow['end']);
                        }
                    });

                    $this->clientRows->each(function($clientRow) use ($event) {
                        if ($clientRow['start'] <= $clientRow['end']) {
                            $event->sheet->mergeCells("A".$clientRow['start'].":A".$clientRow['end']);
                            $event->sheet->mergeCells("K".$clientRow['start'].":K".$clientRow['end']);
                            $event->sheet->mergeCells("L".$clientRow['start'].":L".$clientRow['end']);
                            $event->sheet->mergeCells("P".$clientRow['start'].":P".$clientRow['end']);
                            $event->sheet->mergeCells("Q".$clientRow['start'].":Q".$clientRow['end']);
                            $event->sheet->mergeCells("R".$clientRow['start'].":R".$clientRow['end']);
                            $event->sheet->mergeCells("S".$clientRow['start'].":S".$clientRow['end']);
                        }
                    });

                    $this->guestRows->each(function($guestRow) use ($event) {
                        if ($guestRow['start'] <= $guestRow['end']) {
                            $event->sheet->mergeCells("B".$guestRow['start'].":B".$guestRow['end']);
                            $event->sheet->mergeCells("I".$guestRow['start'].":I".$guestRow['end']);
                            $event->sheet->mergeCells("J".$guestRow['start'].":J".$guestRow['end']);
                        }
                    });
                }
            ];
        }
    }
}

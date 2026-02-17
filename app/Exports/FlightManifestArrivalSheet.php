<?php

namespace App\Exports;

use App\Models\Group;
use App\Models\Booking;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class FlightManifestArrivalSheet implements FromCollection, WithHeadings, ShouldAutoSize, WithTitle, WithStyles
{
    private $group;

    const EMPTY_CELL = null;

    public function __construct(Group $group)
    {
        $this->group = $group;
    }

    public function title(): string
    {
        return 'Arrivals';
    }

    public function collection()
    {
        $flight_manifests = collect();

        $bookings = $this->group->bookings()
            ->with([
                'clients' => function ($query) {
                    $query->with([
                        'guests.flight_manifest.arrivalAirport',
                    ])->whereHas('guests');
                },
            ])
            ->ordered()
            ->get();
        
        foreach ($bookings as $booking) {
            foreach ($booking->clients as $client) {
                foreach ($client->guests as $guest) {
                    if ($guest->transportation && in_array($guest->transportation_type, [ Booking::TRANSPORTATION_TYPE_ROUND_TRIP, Booking::TRANSPORTATION_TYPE_ONE_WAY_AIRPORT_TO_HOTEL])){
                        if (!is_null($guest->flight_manifest)) {
                            $flight_manifests->push([
                                'booking_order' => $booking->order,
                                'guest_name' => $guest->name,
                                'client_email' => $client->client->email,
                                'phone' => $guest->flight_manifest->phone_number,
                                'arrival_airport' => isset($guest->flight_manifest->arrivalAirport) ? $guest->flight_manifest->arrivalAirport->airport_code : null,
                                'arrival_flight' => isset($guest->flight_manifest->arrival_airline) ?  $guest->flight_manifest->getAirlineName($guest->flight_manifest->arrival_airline) . ' ' . $guest->flight_manifest->arrival_number : null,
                                'arrival_date' => isset($guest->flight_manifest->arrival_datetime) ? Carbon::parse($guest->flight_manifest->arrival_datetime, 'UTC')->setTimezone(isset($guest->flight_manifest->arrivalAirport) ? $guest->flight_manifest->arrivalAirport->timezone : 'UTC') : null,
                            ]);
                        } else {
                            $flight_manifests->push([
                                'booking_order' => $booking->order,
                                'guest_name' => $guest->name,
                                'client_email' => $client->client->email,
                                'phone' => null,
                                'arrival_airport' => null,
                                'arrival_flight' => null,
                                'arrival_date' => null,
                            ]);
                        }
                    }
                }
            }
        }

        $sorted_flight_manifests = $flight_manifests->sortBy(function ($manifest) {
            return [
                $manifest['arrival_date'] ? strtotime($manifest['arrival_date']) : PHP_INT_MAX,
                $manifest['booking_order'],
            ];
        })->values();

        $ordered_manifests = collect();
        $previous_order = null;

        foreach ($sorted_flight_manifests as $manifest) {
            if ($manifest['booking_order'] === $previous_order) {
                $manifest['booking_order'] = '';
            } else {
                $previous_order = $manifest['booking_order'];
            }

            $manifest['arrival_time'] = $manifest['arrival_date'] ? $manifest['arrival_date']->format('H:i') : null;
            $manifest['arrival_date'] = $manifest['arrival_date'] ? $manifest['arrival_date']->format('M d, Y') : null;
            $ordered_manifests->push($manifest);
        }

        return $ordered_manifests;
    }

    public function headings(): array
    {
        return [
            'Room #',
            'Guest Name',
            'Email',
            'Phone',
            'Arrival Airport',
            'Arrival Flight',
            'Arrival Date',
            'Arrival Time',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $headerRange = 'A1:H1';
        $sheet->getRowDimension(1)->setRowHeight(1.5 * 15);

        $sheet->getStyle($headerRange)->applyFromArray([
            'font' => ['bold' => true],
            'borders' => [
                'outline' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THICK,
                    'color' => ['argb' => '000000'],
                ],
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FFA500'],
            ],
        ]);
    
        foreach (range('A', 'H') as $column) {
            $sheet->getStyle($column)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        }
    
        $highestRow = $sheet->getHighestRow();
        $bookingGroups = [];
        $currentGroup = [];
    
        for ($row = 2; $row <= $highestRow; $row++) {
            $sheet->getRowDimension($row)->setRowHeight(1.5 * 15);
            $bookingOrder = $sheet->getCell("A$row")->getValue();

            if (!empty($bookingOrder)) {
                if (!empty($currentGroup)) {
                    $bookingGroups[] = $currentGroup;
                    $currentGroup = [];
                }
            }

            $currentGroup[] = $row;
        }
    
        if (!empty($currentGroup)) {
            $bookingGroups[] = $currentGroup;
        }
    
        foreach ($bookingGroups as $group) {
            if (empty($group)) continue;
    
            $firstRow = reset($group);
            $lastRow = end($group);
            $range = "A{$firstRow}:H{$lastRow}";

            if ($firstRow !== $lastRow) {
                $sheet->mergeCells("A{$firstRow}:A{$lastRow}");
                $sheet->getStyle("A{$firstRow}")->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
            }
    
            $sheet->getStyle($range)->applyFromArray([
                'borders' => [
                    'outline' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        'color' => ['argb' => '000000'],
                    ],
                ],
            ]);
        }
    
        return [];
    }
}

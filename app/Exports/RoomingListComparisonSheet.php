<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class RoomingListComparisonSheet implements FromCollection, WithHeadings, ShouldAutoSize, WithStyles, WithEvents, WithTitle
{
    private $report;
    private $rowCount = 1;

    public function __construct(array $report)
    {
        $this->report = $report;
    }

    public function title(): string
    {
        return 'Comparison Report';
    }

    public function collection()
    {
        $rows = collect();

        // Add summary section
        $rows->push(['SUMMARY']);
        $rows->push(['Total Bookings', $this->report['summary']['totalBookings']]);
        $rows->push(['System Guest Count', $this->report['summary']['systemGuestCount']]);
        $rows->push(['Spreadsheet Guest Count', $this->report['summary']['spreadsheetGuestCount']]);
        $rows->push(['Guest Count Match', $this->report['summary']['guestCountMatch'] ? 'Yes' : 'No']);
        $rows->push(['Bookings with Discrepancies', $this->report['summary']['bookingsWithDiscrepancies']]);
        $rows->push(['']); // Empty row

        // Add discrepancies section if any
        if (!empty($this->report['discrepancies'])) {
            $rows->push(['DISCREPANCIES']);
            $rows->push(['']); // Empty row

            foreach ($this->report['discrepancies'] as $discrepancy) {
                $rows->push([
                    'Booking #' . $discrepancy['bookingNumber'],
                    'Guest: ' . $discrepancy['guestName']
                ]);

                foreach ($discrepancy['issues'] as $issue) {
                    $rows->push([
                        '',
                        $issue['field'],
                        'System: ' . $issue['systemValue'],
                        'Spreadsheet: ' . $issue['spreadsheetValue']
                    ]);
                }

                $rows->push(['']); // Empty row between bookings
            }
        } else {
            $rows->push(['NO DISCREPANCIES FOUND']);
            $rows->push(['The rooming list perfectly matches the system data.']);
        }

        // Add notes section
        if (!empty($this->report['notes'])) {
            $rows->push(['']); // Empty row
            $rows->push(['NOTES']);
            foreach ($this->report['notes'] as $note) {
                $rows->push([$note]);
            }
        }

        return $rows;
    }

    public function headings(): array
    {
        return [
            'Booking #',
            'Guest Name',
            'Field',
            'System Value',
            'Spreadsheet Value'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $styles = [
            1 => [
                'font' => ['bold' => true, 'size' => 12],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E8E8E8']
                ]
            ]
        ];

        // Style summary section header
        $styles[2] = [
            'font' => ['bold' => true, 'size' => 14],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4472C4']
            ],
            'font' => ['color' => ['rgb' => 'FFFFFF'], 'bold' => true]
        ];

        return $styles;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $highestRow = $sheet->getHighestRow();
                $highestColumn = $sheet->getHighestColumn();

                // Apply borders to all cells
                $sheet->getStyle('A1:' . $highestColumn . $highestRow)->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => 'CCCCCC']
                        ]
                    ]
                ]);

                // Style section headers
                $currentRow = 2;
                while ($currentRow <= $highestRow) {
                    $cellValue = $sheet->getCell('A' . $currentRow)->getValue();
                    
                    if ($cellValue === 'SUMMARY' || $cellValue === 'DISCREPANCIES' || $cellValue === 'NOTES' || $cellValue === 'NO DISCREPANCIES FOUND') {
                        $sheet->getStyle('A' . $currentRow . ':' . $highestColumn . $currentRow)->applyFromArray([
                            'font' => ['bold' => true, 'size' => 12],
                            'fill' => [
                                'fillType' => Fill::FILL_SOLID,
                                'startColor' => ['rgb' => '4472C4']
                            ],
                            'font' => ['color' => ['rgb' => 'FFFFFF'], 'bold' => true]
                        ]);
                    }

                    // Style booking headers
                    if (strpos($cellValue, 'Booking #') === 0) {
                        $sheet->getStyle('A' . $currentRow . ':B' . $currentRow)->applyFromArray([
                            'font' => ['bold' => true],
                            'fill' => [
                                'fillType' => Fill::FILL_SOLID,
                                'startColor' => ['rgb' => 'F2F2F2']
                            ]
                        ]);
                    }

                    $currentRow++;
                }

                // Set column widths for better readability
                $sheet->getColumnDimension('A')->setWidth(15);
                $sheet->getColumnDimension('B')->setWidth(30);
                $sheet->getColumnDimension('C')->setWidth(40);
                $sheet->getColumnDimension('D')->setWidth(40);

                // Center align headers
                $sheet->getStyle('A1:' . $highestColumn . '1')->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER);

                // Wrap text for long values
                $sheet->getStyle('C2:D' . $highestRow)->getAlignment()
                    ->setWrapText(true);
            }
        ];
    }
}

<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class RoomingListComparisonExport implements WithMultipleSheets
{
    use Exportable;

    protected $report;

    public function __construct(array $report)
    {
        $this->report = $report;
    }

    public function sheets(): array
    {
        return [
            new RoomingListComparisonSheet($this->report),
        ];
    }
}

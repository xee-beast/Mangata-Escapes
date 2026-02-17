<?php

namespace App\Exports;

use App\Models\Group;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class BookingExport implements WithMultipleSheets
{
    use Exportable;

    protected $group;

    public function __construct(Group $group)
    {
        $this->group = $group;
    }

    public function sheets(): array
    {
        return [
            new BookingSheet($this->group),
        ];
    }
}

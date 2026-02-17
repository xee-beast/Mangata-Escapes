<?php

namespace App\Exports;

use App\Models\Group;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class FlightManifestExport implements WithMultipleSheets
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
            new FlightManifestArrivalSheet($this->group),
            new FlightManifestDepartureSheet($this->group),
        ];
    }
}

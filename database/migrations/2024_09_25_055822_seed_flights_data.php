<?php

use App\Models\Airline;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class SeedFlightsData extends Migration
{
    protected $airlines = [
        ['name' => 'American Airlines', 'iata_code' => 'AA'],
        ['name' => 'Alaska Airlines', 'iata_code' => 'AS'],
        ['name' => 'Air Canada', 'iata_code' => 'AC'],
        ['name' => 'Air Transat', 'iata_code' => 'TS'],
        ['name' => 'Air France', 'iata_code' => 'AF'],
        ['name' => 'Air India', 'iata_code' => 'AI'],
        ['name' => 'WestJet', 'iata_code' => 'WS'],
        ['name' => 'World2Fly', 'iata_code' => '2W'],
        ['name' => 'British Airways', 'iata_code' => 'BA'],
        ['name' => 'Copa', 'iata_code' => 'CM'],
        ['name' => 'Avianca', 'iata_code' => 'AV'],
        ['name' => 'Volaris', 'iata_code' => 'Y4'],
        ['name' => 'Aeromexico', 'iata_code' => 'AM'],
        ['name' => 'Sun country', 'iata_code' => 'SY'],
        ['name' => 'Intercaribbean', 'iata_code' => 'JY'],
        ['name' => 'United', 'iata_code' => 'UA'],
        ['name' => 'Delta', 'iata_code' => 'DL'],
        ['name' => 'JetBlue', 'iata_code' => 'B6'],
        ['name' => 'Southwest', 'iata_code' => 'WN'],
        ['name' => 'Frontier', 'iata_code' => 'F9'],
        ['name' => 'Spirit', 'iata_code' => 'NK'],
        ['name' => 'VivaAerobus', 'iata_code' => 'VB'],
        ['name' => 'Arajet', 'iata_code' => 'DM'],
    ];

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        foreach ($this->airlines as $airline) {
            Airline::firstOrCreate(
                ['name' => $airline['name']],
                ['iata_code' => $airline['iata_code']]
            );
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Airline::whereIn('name', array_column($this->airlines, 'name'))->delete();
    }
}

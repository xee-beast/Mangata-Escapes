<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RemoveAirportsColumnsInFlightManifestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('flight_manifests', function (Blueprint $table) {
            $table->dropColumn('arrival_airport_id');
            $table->dropColumn('departure_airport_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('flight_manifests', function (Blueprint $table) {
            //
        });
    }
}

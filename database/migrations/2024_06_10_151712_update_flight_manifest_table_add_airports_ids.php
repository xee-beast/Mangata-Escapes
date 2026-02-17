<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateFlightManifestTableAddAirportsIds extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('flight_manifests', function (Blueprint $table) {
            $table->unsignedBigInteger('arrival_airport_id')->nullable(true)->after('arrival_number');
            $table->unsignedBigInteger('departure_airport_id')->nullable(true)->after('departure_number');
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
            $table->dropColumn('arrival_airport_id');
            $table->dropColumn('departure_airport_id');
        });
    }
}

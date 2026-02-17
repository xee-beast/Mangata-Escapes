<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateFlightManifestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('flight_manifests', function (Blueprint $table) {
            $table->date('arrival_departure_date')->nullable()->after('phone_number');
            $table->string('arrival_departure_airport_timezone')->nullable()->after('phone_number');
            $table->string('arrival_departure_airport_iata')->nullable()->after('phone_number');
            $table->renameColumn('arrival_date', 'arrival_datetime');
            $table->renameColumn('departure_date', 'departure_datetime');
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
            $table->renameColumn('arrival_datetime', 'arrival_date');
            $table->renameColumn('departure_datetime', 'departure_date');
            $table->dropColumn(['arrival_departure_date', 'arrival_departure_airport_timezone', 'arrival_departure_airport_iata']);
        });
    }
}

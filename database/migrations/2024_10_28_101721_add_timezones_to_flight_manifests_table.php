<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTimezonesToFlightManifestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('flight_manifests', function (Blueprint $table) {
            $table->string('arrival_timezone')->after('arrival_date')->nullable();
            $table->string('departure_timezone')->after('departure_date')->nullable();
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
            $table->dropColumn('arrival_timezone');
            $table->dropColumn('departure_timezone');
        });
    }
}

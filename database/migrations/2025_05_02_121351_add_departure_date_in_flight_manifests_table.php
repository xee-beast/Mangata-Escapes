<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDepartureDateInFlightManifestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('flight_manifests', function (Blueprint $table) {
            $table->date('departure_date')->nullable()->after('arrival_date_mismatch_reason');
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
            $table->dropColumn('departure_date');
        });
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAirportColumnsInFlightManifestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('flight_manifests', function (Blueprint $table) {
            $table->unsignedInteger('arrival_airport_id')->after('arrival_date')->nullable();
            $table->foreign('arrival_airport_id')->references('id')->on('airports')->onDelete('cascade');
            $table->unsignedInteger('departure_airport_id')->after('departure_date')->nullable();
            $table->foreign('departure_airport_id')->references('id')->on('airports')->onDelete('cascade');
            $table->boolean('arrival_manual')->after('arrival_number')->default(0)->nullable();
            $table->boolean('departure_manual')->after('departure_number')->default(0)->nullable();
            $table->text('arrival_date_mismatch_reason')->after('arrival_manual')->nullable();
            $table->text('departure_date_mismatch_reason')->after('departure_manual')->nullable();
            $table->dropColumn(['arrival_timezone', 'departure_timezone']);
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
            $table->dropForeign('flight_manifests_arrival_airport_id_foreign');
            $table->dropColumn('arrival_airport_id');
            $table->dropForeign('flight_manifests_departure_airport_id_foreign');
            $table->dropColumn('departure_airport_id');
            $table->dropColumn(['arrival_manual', 'departure_manual', 'arrival_date_mismatch_reason', 'departure_date_mismatch_reason']);
            $table->string('arrival_timezone')->after('arrival_date')->nullable();
            $table->string('departure_timezone')->after('departure_date')->nullable();
        });
    }
}

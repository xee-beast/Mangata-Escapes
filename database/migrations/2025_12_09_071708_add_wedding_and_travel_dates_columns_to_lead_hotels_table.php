<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddWeddingAndTravelDatesColumnsToLeadHotelsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('lead_hotels', function (Blueprint $table) {
            $table->date('travel_end_date')->after('requested_on')->nullable();
            $table->date('travel_start_date')->after('requested_on')->nullable();
            $table->date('wedding_date')->after('requested_on')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('lead_hotels', function (Blueprint $table) {
            $table->dropColumn(['travel_end_date', 'travel_start_date', 'wedding_date']);
        });
    }
}

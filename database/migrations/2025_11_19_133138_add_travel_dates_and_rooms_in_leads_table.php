<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTravelDatesAndRoomsInLeadsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->date('travel_end_date')->after('wedding_date')->nullable();
            $table->date('travel_start_date')->after('wedding_date')->nullable();
            $table->integer('number_of_rooms')->after('number_of_people')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropColumn(['number_of_rooms', 'travel_start_date', 'travel_end_date']);
        });
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateHotelAirportRatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('hotel_airport_rates', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('hotel_id');
            $table->foreign('hotel_id')->references('id')->on('hotels')->onDelete('cascade');
            $table->unsignedInteger('airport_id');
            $table->foreign('airport_id')->references('id')->on('airports')->onDelete('cascade');
            $table->decimal('transportation_rate', 6, 2)->nullable();
            $table->decimal('single_transportation_rate', 6, 2)->nullable();
            $table->decimal('one_way_transportation_rate', 6, 2)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('hotel_airport_rates');
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFitRatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('fit_rates', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('booking_client_id');
            $table->foreign('booking_client_id')->references('id')->on('booking_clients')->onDelete('cascade');
            $table->decimal('accommodation', 8, 2);
            $table->decimal('insurance', 8, 2);
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
        Schema::dropIfExists('fit_rates');
    }
}

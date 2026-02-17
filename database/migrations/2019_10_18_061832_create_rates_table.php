<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('rates', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('room_block_id');
            $table->foreign('room_block_id')->references('id')->on('room_blocks')->onDelete('cascade');
            $table->unsignedSmallInteger('occupancy');
            $table->decimal('rate', 6, 2);
            $table->decimal('provider_rate', 6, 2);
            $table->decimal('split_rate', 6, 2)->nullable();
            $table->decimal('split_provider_rate', 6, 2)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('rates');
    }
}

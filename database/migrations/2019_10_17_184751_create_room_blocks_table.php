<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRoomBlocksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('room_blocks', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('hotel_block_id');
            $table->foreign('hotel_block_id')->references('id')->on('hotel_blocks')->onDelete('cascade');
            $table->unsignedInteger('room_id');
            $table->foreign('room_id')->references('id')->on('rooms');
            $table->date('start_date');
            $table->date('end_date');
            $table->date('split_date')->nullable();
            $table->unsignedSmallInteger('inventory')->nullable();
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
        Schema::dropIfExists('room_blocks');
    }
}

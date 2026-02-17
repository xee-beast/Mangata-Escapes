<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBookingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('group_id');
            $table->foreign('group_id')->references('id')->on('groups');
            $table->char('reservation_code', 6)->unique();
            $table->unsignedInteger('room_block_id');
            $table->foreign('room_block_id')->references('id')->on('room_blocks');
            $table->unsignedInteger('bed_id');
            $table->foreign('bed_id')->references('id')->on('beds');
            $table->date('check_in');
            $table->date('check_out');
            $table->text('special_requests')->nullable();
            $table->boolean('transportation')->default(true);
            $table->boolean('insurance');
            $table->unsignedSmallInteger('order');
            $table->timestamp('confirmed_at')->nullable();
            $table->softDeletes();
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
        Schema::dropIfExists('bookings');
    }
}

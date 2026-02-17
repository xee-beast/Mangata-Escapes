<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RemoveColumnsFromBookingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropForeign('bookings_room_block_id_foreign');
            $table->dropColumn('room_block_id');
            $table->dropColumn('bed');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('bookings', function (Blueprint $table) {
            
            $table->string('bed')->after('group_id')->nullable();
            $table->unsignedInteger('room_block_id')->after('group_id')->nullable();
            $table->foreign('room_block_id')->references('id')->on('room_blocks');
        });
    }
}

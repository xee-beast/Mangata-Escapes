<?php

use App\Models\RoomBlock;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateRatioColumnOnRoomBlocksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        RoomBlock::without('room')->whereNull('min_adults_per_child')->orWhereNull('max_children_per_adult')->get()->each(function ($room_block) {
            $room_block->update([
                'min_adults_per_child' => is_null($room_block->min_adults_per_child) ? 1 : $room_block->min_adults_per_child,
                'max_children_per_adult' => is_null($room_block->max_children_per_adult) ? 1 : $room_block->max_children_per_adult
            ]);
        });

        Schema::table('room_blocks', function (Blueprint $table) {
            $table->unsignedSmallInteger('min_adults_per_child')->nullable(false)->change();
            $table->unsignedSmallInteger('max_children_per_adult')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('room_blocks', function (Blueprint $table) {
            $table->unsignedSmallInteger('min_adults_per_child')->nullable(true)->change();
            $table->unsignedSmallInteger('max_children_per_adult')->nullable(true)->change();
        });
    }
}

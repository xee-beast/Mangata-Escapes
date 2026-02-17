<?php

use App\Models\RoomBlock;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddColumnsToRoomBlocksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('room_blocks', function (Blueprint $table) {
            $table->unsignedSmallInteger('max_children_per_adult')->nullable()->after('room_id');
            $table->unsignedSmallInteger('min_adults_per_child')->nullable()->after('room_id');
        });

        RoomBlock::without('room')->join('rooms', 'room_blocks.room_id', '=', 'rooms.id')->update([
            'room_blocks.min_adults_per_child' => DB::raw('rooms.min_adults_per_child'),
            'room_blocks.max_children_per_adult' => DB::raw('rooms.max_children_per_adult')
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('room_blocks', function (Blueprint $table) {
            $table->dropColumn(['max_children_per_adult', 'min_adults_per_child']);
        });
    }
}

<?php

use App\Models\Room;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddColumnsToRoomsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('rooms', function (Blueprint $table) {
            $table->unsignedSmallInteger('max_children')->nullable()->after('max_occupants');
            $table->unsignedSmallInteger('max_adults')->nullable()->after('max_occupants');
            $table->boolean('adults_only')->default(false)->after('max_occupants');
        });

        Room::whereNotNull('min_adults_per_child')->whereNotNull('max_children_per_adult')->update([
            'max_adults' => DB::raw('max_occupants'),
            'max_children' => DB::raw('FLOOR(max_occupants / ((min_adults_per_child / max_children_per_adult) + 1))'),
        ]);

        Room::whereNull('min_adults_per_child')->whereNull('max_children_per_adult')->update([
            'adults_only' => true,
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('rooms', function (Blueprint $table) {
            $table->dropColumn(['adults_only', 'max_adults', 'max_children']);
        });
    }
}

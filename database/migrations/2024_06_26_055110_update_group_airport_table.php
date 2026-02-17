<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateGroupAirportTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('group_airports', function(Blueprint $table) {
            $table->renameColumn('price', 'transportation_rate');
            $table->decimal('single_transportation_rate', 6, 2);
            $table->decimal('one_way_transportation_rate', 6, 2);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('group_airports', function(Blueprint $table) {
            $table->renameColumn('transportation_rate', 'price');
            $table->removeColumn('single_transportation_rate', 6, 2);
            $table->removeColumn('one_way_transportation_rate', 6, 2);
        });
    }
}

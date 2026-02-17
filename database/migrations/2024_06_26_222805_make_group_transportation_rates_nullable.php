<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class MakeGroupTransportationRatesNullable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('groups', function (Blueprint $table) {
            $table->string('transportation_rate')->nullable()->change();
            $table->string('single_transportation_rate')->nullable()->change();
            $table->string('one_way_transportation_rate')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('groups', function (Blueprint $table) {
            $table->string('transportation_rate')->nullable(false)->change();
            $table->string('single_transportation_rate')->nullable(false)->change();
            $table->string('one_way_transportation_rate')->nullable(false)->change();

        });
    }
}

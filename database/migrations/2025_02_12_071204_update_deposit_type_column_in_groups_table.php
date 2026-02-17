<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateDepositTypeColumnInGroupsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('groups', function (Blueprint $table) {
            DB::statement("ALTER TABLE `groups` CHANGE `deposit_type` `deposit_type` ENUM('fixed', 'nights', 'percentage', 'per person') NOT NULL DEFAULT 'fixed'");
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
            DB::statement("ALTER TABLE `groups` CHANGE `deposit_type` `deposit_type` ENUM('fixed', 'per person') NOT NULL DEFAULT 'fixed'");
        });
    }
}

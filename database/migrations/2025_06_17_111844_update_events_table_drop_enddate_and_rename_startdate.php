<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateEventsTableDropEnddateAndRenameStartdate extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn('end_date');
        });
        
        Schema::table('events', function (Blueprint $table) {
            $table->renameColumn('start_date', 'date');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('events', function (Blueprint $table) {
            $table->renameColumn('date', 'start_date');
        });
        
        Schema::table('events', function (Blueprint $table) {
            $table->date('end_date')->nullable()->after('start_date');
        });
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnsToLeadsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->boolean('is_canadian')->after('id')->default(0);
            $table->boolean('is_fit')->after('id')->default(0);
            $table->string('travel_agent_requested')->nullable()->after('status');
            $table->integer('number_of_people')->after('email')->nullable();
            $table->string('venue')->after('email')->nullable();
            $table->date('cancellation_date')->after('last_attempt')->nullable();
            $table->date('balance_due_date')->after('last_attempt')->nullable();
            $table->date('release_rooms_by')->after('last_attempt')->nullable();
            $table->date('contacted_us_date')->after('notes')->nullable();
            $table->date('responded_on')->after('last_attempt')->nullable();
            $table->string('bride_first_name')->nullable(true)->change();
            $table->string('bride_last_name')->nullable(true)->change();
            $table->string('groom_first_name')->nullable(true)->change();
            $table->string('groom_last_name')->nullable(true)->change();
            $table->string('phone')->nullable(true)->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropColumn(['is_fit', 'is_canadian', 'travel_agent_requested', 'venue', 'number_of_people', 'release_rooms_by', 'balance_due_date', 'cancellation_date', 'contacted_us_date', 'responded_on']);
        });
    }
}

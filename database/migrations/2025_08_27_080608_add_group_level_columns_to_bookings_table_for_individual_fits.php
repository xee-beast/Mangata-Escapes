<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddGroupLevelColumnsToBookingsTableForIndividualFits extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->text('staff_message')->nullable()->after('flight_message');
            $table->decimal('change_fee_amount', 10, 2)->nullable()->after('flight_message');
            $table->date('change_fee_date')->nullable()->after('flight_message');
            $table->string('id_at_provider')->nullable()->after('flight_message');
            $table->unsignedInteger('provider_id')->nullable()->after('flight_message');
            $table->foreign('provider_id')->references('id')->on('providers')->nullOnDelete();
            $table->unsignedInteger('travel_agent_id')->nullable()->after('flight_message');
            $table->foreign('travel_agent_id')->references('id')->on('travel_agents')->nullOnDelete();
            $table->string('reservation_leader_last_name')->nullable()->after('flight_message');
            $table->string('reservation_leader_first_name')->nullable()->after('flight_message');
            $table->string('email')->nullable()->after('flight_message');
            $table->unsignedInteger('destination_id')->after('flight_message')->nullable();
            $table->foreign('destination_id')->references('id')->on('destinations')->nullOnDelete();
            $table->unsignedBigInteger('transfer_id')->nullable()->after('flight_message');
            $table->foreign('transfer_id')->references('id')->on('transfers')->nullOnDelete();
            $table->date('transportation_submit_before')->nullable()->after('flight_message');
            $table->enum('transportation_type', ['private', 'shared'])->nullable()->after('flight_message'); 
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
            $table->dropForeign(['transfer_id']);
            $table->dropForeign(['destination_id']);
            $table->dropForeign(['travel_agent_id']);
            $table->dropForeign(['provider_id']);
            
            $table->dropColumn([
                'transportation_type',
                'transportation_submit_before',
                'transfer_id',
                'destination_id',
                'email',
                'reservation_leader_first_name',
                'reservation_leader_last_name',
                'travel_agent_id',
                'provider_id',
                'id_at_provider',
                'change_fee_date',
                'change_fee_amount',
                'staff_message',
            ]);
        });
    }
}

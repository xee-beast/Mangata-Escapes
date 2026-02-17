<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIndividualBookingColumnsToBookingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropForeign(['custom_group_airport']);
            $table->dropColumn('custom_group_airport');
            $table->unsignedInteger('group_id')->nullable()->change();
            $table->unsignedSmallInteger('order')->nullable()->change();
            $table->string('flight_message')->after('deposit_type')->nullable();
            $table->string('known_traveler_number')->after('deposit_type')->nullable();
            $table->string('airline_membership_number')->after('deposit_type')->nullable();
            $table->text('flight_preferences')->after('deposit_type')->nullable();
            $table->string('departure_gateway')->after('deposit_type')->nullable();
            $table->boolean('transportation')->after('deposit_type')->nullable();
            $table->decimal('budget', 12, 2)->after('deposit_type')->nullable();
            $table->date('check_out')->after('deposit_type')->nullable();
            $table->date('check_in')->after('deposit_type')->nullable();
            $table->string('room_category_name')->after('deposit_type')->nullable();
            $table->boolean('room_category')->after('deposit_type')->nullable();
            $table->string('hotel_name')->after('deposit_type')->nullable();
            $table->text('hotel_preferences')->after('deposit_type')->nullable();
            $table->boolean('hotel_assistance')->after('deposit_type')->nullable();
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
            $table->unsignedInteger('custom_group_airport')->nullable(true);
            $table->foreign('custom_group_airport')->references('id')->on('group_airports')->onDelete('cascade');
            $table->unsignedInteger('group_id')->nullable(false)->change();
            $table->unsignedSmallInteger('order')->nullable(false)->change();
            $table->dropColumn(['hotel_assistance', 'hotel_preferences', 'hotel_name', 'room_category', 'room_category_name', 'check_in', 'check_out', 'budget', 'transportation', 'departure_gateway', 'flight_preferences', 'airline_membership_number', 'known_traveler_number', 'flight_message']);
        });
    }
}

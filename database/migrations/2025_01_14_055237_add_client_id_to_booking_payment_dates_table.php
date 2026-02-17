<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddClientIdToBookingPaymentDatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('booking_payment_dates', function (Blueprint $table) {
            $table->unsignedInteger('booking_client_id')->after('group_id')->nullable();
            $table->foreign('booking_client_id')->references('id')->on('booking_clients')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('booking_payment_dates', function (Blueprint $table) {
            $table->dropForeign(['booking_payment_dates_booking_client_id_foreign']);
            $table->dropColumn('booking_client_id');
        });
    }
}

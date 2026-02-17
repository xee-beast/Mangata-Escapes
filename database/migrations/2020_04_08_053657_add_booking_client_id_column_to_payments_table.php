<?php

use App\Models\BookingClient;
use App\Models\Payment;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddBookingClientIdColumnToPaymentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->unsignedInteger('booking_client_id')->nullable()->after('id');
            $table->foreign('booking_client_id')->references('id')->on('booking_clients')->onDelete('cascade');
        });

        foreach (BookingClient::all() as $bookingClient) {
            Payment::where('booking_id', $bookingClient->booking_id)->whereHas('card', function ($query) use ($bookingClient) {
                $query->where('client_id', $bookingClient->client_id);
            })->update(['booking_client_id' => $bookingClient->id]);
        }

        Schema::table('payments', function (Blueprint $table) {
            $table->unsignedInteger('booking_client_id')->nullable(false)->change();
            $table->dropForeign(['booking_id']);
            $table->dropColumn('booking_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->unsignedInteger('booking_id')->nullable()->after('id');
            $table->foreign('booking_id')->references('id')->on('bookings')->onDelete('cascade');
        });

        foreach (BookingClient::all() as $bookingClient) {
            $bookingClient->payments()->update(['booking_id' => $bookingClient->booking_id]);
        }

        Schema::table('payments', function (Blueprint $table) {
            $table->unsignedInteger('booking_id')->nullable(false)->change();
            $table->dropForeign(['booking_client_id']);
            $table->dropColumn('booking_client_id');
        });
    }
}

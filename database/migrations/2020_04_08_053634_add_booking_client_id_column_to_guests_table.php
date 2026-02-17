<?php

use App\Models\BookingClient;
use App\Models\Guest;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddBookingClientIdColumnToGuestsTable extends Migration
{
    /**
     * Setup the migration so that it ignores that the table has an enum type column.
     */
    public function __construct()
    {
        DB::getDoctrineSchemaManager()->getDatabasePlatform()->registerDoctrineTypeMapping('enum', 'string');
    }

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('guests', function (Blueprint $table) {
            $table->unsignedInteger('booking_client_id')->nullable()->after('id');
            $table->foreign('booking_client_id')->references('id')->on('booking_clients')->onDelete('cascade');
        });

        foreach (BookingClient::all() as $bookingClient) {
            Guest::where('booking_id', $bookingClient->booking_id)->where('client_id', $bookingClient->client_id)->update(['booking_client_id' => $bookingClient->id]);
        }

        Schema::table('guests', function (Blueprint $table) {
            $table->unsignedInteger('booking_client_id')->nullable(false)->change();
            $table->dropForeign(['booking_id']);
            $table->dropColumn('booking_id');
            $table->dropForeign(['client_id']);
            $table->dropColumn('client_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('guests', function (Blueprint $table) {
            $table->unsignedInteger('client_id')->nullable()->after('id');
            $table->foreign('client_id')->references('id')->on('clients');
            $table->unsignedInteger('booking_id')->nullable()->after('id');
            $table->foreign('booking_id')->references('id')->on('bookings')->onDelete('cascade');
        });

        foreach (BookingClient::all() as $bookingClient) {
            $bookingClient->guests()->update(['booking_id' => $bookingClient->booking_id, 'client_id' => $bookingClient->client_id]);
        }

        Schema::table('guests', function (Blueprint $table) {
            $table->unsignedInteger('client_id')->nullable(false)->change();
            $table->unsignedInteger('booking_id')->nullable(false)->change();
            $table->dropForeign(['booking_client_id']);
            $table->dropColumn('booking_client_id');
        });
    }
}

<?php

use App\Libraries\Doctrine\CharType;
use App\Models\BookingClient;
use Doctrine\DBAL\Types\Type;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class AddColumnsToBookingClients extends Migration
{
    /**
     * Setup the migration so that we can modify a char type column.
     */
    public function __construct()
    {
        if (!Type::hasType('char')) {
            Type::addType('char', CharType::class);
        }
    }

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('booking_clients', function (Blueprint $table) {
            $table->string('last_name')->nullable()->after('client_id');
            $table->string('first_name')->nullable()->after('client_id');
            $table->char('reservation_code', 6)->nullable()->after('id');
        });

        foreach (BookingClient::with('guests')->get() as $bookingClient) {
            $code = strtoupper(Str::random(6));
            while (BookingClient::where('reservation_code', $code)->exists()) {
                $code = strtoupper(Str::random(6));
            }

            $bookingClient->fill([
                'first_name' => $bookingClient->guests->count() ? $bookingClient->guests->first()->first_name : '',
                'last_name' => $bookingClient->guests->count() ? $bookingClient->guests->first()->last_name : ''
            ]);
            $bookingClient->reservation_code = $code;
            $bookingClient->save();
        }

        Schema::table('booking_clients', function (Blueprint $table) {
            $table->string('last_name')->nullable(false)->change();
            $table->string('first_name')->nullable(false)->change();
            $table->char('reservation_code', 6)->unique()->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('booking_clients', function (Blueprint $table) {
            $table->dropColumn(['reservation_code', 'first_name', 'last_name']);
        });
    }
}

<?php

use App\Models\Booking;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class MoveInsuranceToGuestLevel extends Migration
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
        Schema::table('booking_clients', function (Blueprint $table) {
            $table->timestamp('insurance_signed_at')->nullable()->after('telephone');
            $table->boolean('insurance')->nullable()->after('telephone');
        });

        Schema::table('guests', function (Blueprint $table) {
            $table->boolean('insurance')->nullable()->after('check_out');
        });

        foreach(Booking::withTrashed()->with('clients')->get() as $booking) {
            foreach($booking->clients as $client) {
                if (!is_null($client->card_id)) {
                    $client->update([
                        'insurance' => $booking->insurance
                    ]);

                    $client->guests()->update([
                        'insurance' => $client->insurance
                    ]);
                }
            }
        }

        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['insurance']);
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
            $table->boolean('insurance')->nullable()->after('transportation');
        });

        foreach(Booking::withTrashed()->with('clients')->get() as $booking) {
            $booking->update([
                'insurance' => $booking->clients->first()->insurance ?? false
            ]);
        }

        Schema::table('bookings', function (Blueprint $table) {
            $table->boolean('insurance')->nullable(false)->change();
        });

        Schema::table('guests', function (Blueprint $table) {
            $table->dropColumn(['insurance']);
        });

        Schema::table('booking_clients', function (Blueprint $table) {
            $table->dropColumn(['insurance', 'insurance_signed_at']);
        });
    }
}

<?php

use App\Libraries\Doctrine\CharType;
use App\Models\Booking;
use Doctrine\DBAL\Types\Type;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class AddAndRemoveColumnsFromBookingsTable extends Migration
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
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn('reservation_code');
            $table->text('notes')->nullable()->after('insurance');
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
            $table->dropColumn('notes');
            $table->char('reservation_code', 6)->nullable()->after('group_id');
        });

        foreach (Booking::all() as $booking) {
            $code = strtoupper(Str::random(6));

            while (Booking::where('reservation_code', $code)->exists()) {
                $code = strtoupper(Str::random(6));
            }

            $booking->reservation_code = $code;
            $booking->save();
        }

        Schema::table('bookings', function (Blueprint $table) {
            $table->char('reservation_code', 6)->unique()->nullable(false)->change();
        });
    }
}

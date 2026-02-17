<?php

use App\Models\Booking;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeBedColumnOnBookingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropForeign(['bed_id']);
            $table->dropColumn('bed_id');
            $table->string('bed')->nullable()->after('room_block_id');
        });

        Booking::withTrashed()->update([
            'bed' => 'One King'
        ]);

        Schema::table('bookings', function (Blueprint $table) {
            $table->string('bed')->nullable(false)->change();
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
            $table->dropColumn('bed');
            $table->unsignedInteger('bed_id')->nullable();
            $table->foreign('bed_id')->references('id')->on('beds');
        });
    }
}

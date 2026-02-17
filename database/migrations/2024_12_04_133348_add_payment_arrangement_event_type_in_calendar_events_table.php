<?php

use App\Models\CalendarEvent;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPaymentArrangementEventTypeInCalendarEventsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        CalendarEvent::firstOrCreate(
            ['name' => 'Booking Payment Arrangement Due Date'],
            ['color' => '#D1B7B7', 'is_default' => true]
        );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        CalendarEvent::where('name', 'Booking Payment Arrangement Due Date')->delete();
    }
}

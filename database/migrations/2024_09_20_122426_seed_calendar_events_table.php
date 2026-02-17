<?php

use App\Models\CalendarEvent;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class SeedCalendarEventsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $events = [
            [
                'name' => 'Cancellation Date',
                'color' => '#D1B7B7',
                'is_default' => true,
            ],
            [
                'name' => 'Balance Due Date',
                'color' => '#D1B7B7',
                'is_default' => true,
            ],
            [
                'name' => 'Due Date',
                'color' => '#D1B7B7',
                'is_default' => true,
            ],
            [
                'name' => 'Attrition Due Date',
                'color' => '#D1B7B7',
                'is_default' => true,
            ]
        ];
        
        foreach ($events as $event) {
            CalendarEvent::firstOrCreate(
                ['name' => $event['name']],
                ['color' => $event['color'], 'is_default' => $event['is_default']]
            );
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        CalendarEvent::whereIn('name', ['Cancellation Date', 'Balance Due Date', 'Due Date', 'Attrition Due Date'])->delete();
    }
}

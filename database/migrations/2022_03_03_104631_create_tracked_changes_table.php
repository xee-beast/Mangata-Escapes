<?php

use App\Jobs\StageBooking;
use App\Models\Booking;
use App\Models\TrackedChange;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class CreateTrackedChangesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tracked_changes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->morphs('trackable');
            $table->json('snapshot');
            $table->unsignedInteger('confirmed_by')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();

            $table->foreign('confirmed_by')->references('id')->on('users')->onDelete('set null');
        });

        Booking::withTrashed()->get('id')->pluck('id')->each(function ($id) {
            StageBooking::dispatch($id);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tracked_changes');
    }
}

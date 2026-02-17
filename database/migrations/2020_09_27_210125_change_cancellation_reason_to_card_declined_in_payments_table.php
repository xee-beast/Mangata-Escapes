<?php

use App\Models\Payment;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeCancellationReasonToCardDeclinedInPaymentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->boolean('card_declined')->nullable()->after('cancelled_at');
        });

        Payment::whereNotNull('cancelled_at')->get()->each(function ($payment) {
            $payment->card_declined = ($payment->cancellation_reason == 'the card was declined');
            $payment->save();
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn(['cancellation_reason']);
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
            $table->string('cancellation_reason')->nullable()->after('cancelled_at');
        });

        Payment::whereNotNull('cancelled_at')->get()->each(function ($payment) {
            $payment->cancellation_reason = $payment->card_declined ? 'the card was declined' : 'something went wrong';
            $payment->save();
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn(['card_declined']);
        });
    }
}

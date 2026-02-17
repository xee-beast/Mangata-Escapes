<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFitQuotesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('fit_quotes', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('booking_client_id');
            $table->foreign('booking_client_id')->references('id')->on('booking_clients')->onDelete('cascade');
            $table->date('expiry_date');
            $table->timestamp('accepted_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('fit_quotes');
    }
}

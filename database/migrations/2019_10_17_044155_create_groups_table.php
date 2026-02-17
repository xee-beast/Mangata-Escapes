<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGroupsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('groups', function (Blueprint $table) {
            $table->increments('id');
            $table->string('bride');
            $table->string('groom');
            $table->string('slug')->unique();
            $table->uuid('image_id')->nullable();
            $table->foreign('image_id')->references('id')->on('images')->onDelete('set null');
            $table->date('event_date');
            $table->unsignedInteger('destination_id');
            $table->foreign('destination_id')->references('id')->on('destinations');
            $table->unsignedInteger('travel_agent_id');
            $table->foreign('travel_agent_id')->references('id')->on('travel_agents');
            $table->unsignedInteger('provider_id');
            $table->foreign('provider_id')->references('id')->on('providers');
            $table->string('id_at_provider')->nullable();
            $table->decimal('deposit')->default(100.00);
            $table->date('cancellation_date');
            $table->date('balance_due_date');
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
        Schema::dropIfExists('groups');
    }
}

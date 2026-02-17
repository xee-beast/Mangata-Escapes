<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('system_notification_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('notification_class')->unique()->comment('Fully qualified class name of the notification');
            $table->boolean('is_active')->default(true)->comment('Whether this notification is active');
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
        Schema::dropIfExists('system_notification_statuses');
    }
};

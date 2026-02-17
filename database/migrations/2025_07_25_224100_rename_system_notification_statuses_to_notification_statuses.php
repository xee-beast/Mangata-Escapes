<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::rename('system_notification_statuses', 'notification_statuses');
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::rename('notification_statuses', 'system_notification_statuses');
    }
};

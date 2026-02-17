<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('notification_logs', function (Blueprint $table) {
            $table->id();
            $table->string('class'); // Full class name of the notification
            $table->json('parameters')->nullable(); // JSON column to store constructor parameters
            $table->timestamps(); // created_at and updated_at timestamps
            
            // Add index for faster lookups by class
            $table->index('class');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('notification_logs');
    }
};

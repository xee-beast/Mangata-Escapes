<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('guest_changes', function (Blueprint $table) {
            $table->json('payment_details')->nullable()->after('id');
        });
    }

    public function down()
    {
        Schema::table('guest_changes', function (Blueprint $table) {
            $table->dropColumn('payment_details');
        });
    }
};

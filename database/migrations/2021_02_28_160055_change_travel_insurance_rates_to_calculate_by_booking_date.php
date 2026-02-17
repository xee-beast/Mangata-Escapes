<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeTravelInsuranceRatesToCalculateByBookingDate extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('insurance_rates', function (Blueprint $table) {
            $table->date('start_date')->nullable()->after('description');
        });

        Schema::table('groups', function (Blueprint $table) {
            $table->boolean('use_fallback_insurance')->default(false)->after('insurance_rate_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('groups', function (Blueprint $table) {
            $table->dropColumn(['use_fallback_insurance']);
        });

        Schema::table('insurance_rates', function (Blueprint $table) {
            $table->dropColumn(['start_date']);
        });
    }
}
<?php

use App\Models\InsuranceRate;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDescriptionColumnToInsuranceRatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('insurance_rates', function (Blueprint $table) {
            $table->text('description')->nullable()->after('name');
        });

        InsuranceRate::query()->update(
            ['description' => 'Travel Insurance is available and highly recommended. Rates are per person, depending on the cost of the land-only portion of your trip. Travel Insurance can be added at any point; however, in order to receive the full benefit of the travel insurance, you must add it within 14 days of booking. Otherwise, the "Cancel For Any Reason" benefit will not apply and the only benefits will be for in-travel issues (per New York State law, CFAR does not apply to any NY residents). Please note that once purchased, travel insurance is nonrefundable.']
        );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('insurance_rates', function (Blueprint $table) {
            $table->dropColumn(['description']);
        });
    }
}

<?php

use App\Models\Provider;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInsuranceRatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('insurance_rates', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('provider_id');
            $table->foreign('provider_id')->references('id')->on('providers');
            $table->string('name');
            $table->enum('type', ['total', 'nights'])->default('total');
            $table->json('rates');
            $table->timestamps();
        });

        Schema::table('groups', function (Blueprint $table) {
            $table->unsignedInteger('insurance_rate_id')->nullable()->after('id_at_provider');
            $table->foreign('insurance_rate_id')->references('id')->on('insurance_rates');
        });

        foreach (Provider::all() as $provider) {
            $insuranceRate = $provider->insurance_rates()->create([
                'name' => $provider->abbreviation . ' Default',
                'rates' => [
                    [
                        'to' => 1250,
                        'rate' => 79,
                    ],
                    [
                        'to' => 2000,
                        'rate' => 89,
                    ],
                    [
                        'to' => 2500,
                        'rate' => 99,
                    ],
                    [
                        'to' => 3000,
                        'rate' => 129,
                    ],
                    [
                        'to' => 4000,
                        'rate' => 159,
                    ],
                    [
                        'to' => 5000,
                        'rate' => 189,
                    ],
                    [
                        'to' => 6000,
                        'rate' => 219,
                    ],
                    [
                        'to' => 7000,
                        'rate' => 259,
                    ],
                    [
                        'to' => 8000,
                        'rate' => 289,
                    ],
                    [
                        'to' => 9000,
                        'rate' => 319,
                    ],
                    [
                        'to' => 10000,
                        'rate' => 359,
                    ],
                    [
                        'to' => 15000,
                        'rate' => 409,
                    ],
                ]
            ]);

            $provider->groups()->update([
                'insurance_rate_id' => $insuranceRate->id
            ]);
        }

        Schema::table('groups', function (Blueprint $table) {
            $table->unsignedInteger('insurance_rate_id')->nullable(false)->change();
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
            $table->dropForeign(['insurance_rate_id']);
            $table->dropColumn('insurance_rate_id');
        });

        Schema::dropIfExists('insurance_rates');
    }
}

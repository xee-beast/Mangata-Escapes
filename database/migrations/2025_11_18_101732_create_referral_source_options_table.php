<?php

use App\Models\ReferralSourceOption;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReferralSourceOptionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('referral_source_options', function (Blueprint $table) {
            $table->id();
            $table->string('option');
            $table->timestamps();
        });

        $options = [
            ['option' => 'Google', 'created_at' => now(), 'updated_at' => now()],
            ['option' => 'Instagram Post', 'created_at' => now(), 'updated_at' => now()],
            ['option' => 'Instagram Ad', 'created_at' => now(), 'updated_at' => now()],
            ['option' => 'TikTok', 'created_at' => now(), 'updated_at' => now()],
            ['option' => 'Reddit', 'created_at' => now(), 'updated_at' => now()],
            ['option' => 'Wedding Wire or The Knot', 'created_at' => now(), 'updated_at' => now()],
            ['option' => 'Referral (please include who)', 'created_at' => now(), 'updated_at' => now()],
            ['option' => 'Facebook Group (please include which)', 'created_at' => now(), 'updated_at' => now()],
            ['option' => 'Facebook Ad', 'created_at' => now(), 'updated_at' => now()],
            ['option' => 'Pinterest', 'created_at' => now(), 'updated_at' => now()],
            ['option' => 'Other', 'created_at' => now(), 'updated_at' => now()],
        ];

        ReferralSourceOption::insert($options);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('referral_source_options');
    }
}

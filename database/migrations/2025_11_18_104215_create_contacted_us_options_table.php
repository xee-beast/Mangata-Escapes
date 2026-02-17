<?php

use App\Models\ContactedUsOption;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateContactedUsOptionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('contacted_us_options', function (Blueprint $table) {
            $table->id();
            $table->string('option');
            $table->timestamps();
        });

        $options = [
            ['option' => 'Contact Us Form', 'created_at' => now(), 'updated_at' => now()],
            ['option' => 'Scheduled Call', 'created_at' => now(), 'updated_at' => now()],
            ['option' => 'Facebook Group Email', 'created_at' => now(), 'updated_at' => now()],
            ['option' => 'Facebook Messages', 'created_at' => now(), 'updated_at' => now()],
            ['option' => 'Instagram Messages', 'created_at' => now(), 'updated_at' => now()],
            ['option' => 'TikTok Messages', 'created_at' => now(), 'updated_at' => now()],
            ['option' => 'Other', 'created_at' => now(), 'updated_at' => now()],
        ];

        ContactedUsOption::insert($options);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('contacted_us_options');
    }
}

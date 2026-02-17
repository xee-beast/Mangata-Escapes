<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\TransportationType;

class TransportationTypesSeed extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        /**
         * Create Transportation types
         */
        TransportationType::create(['description' => 'Round trip']);
        TransportationType::create(['description' => 'One Way Airport to Hotel']);
        TransportationType::create(['description' => 'One Way Hotel to Airport']);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}

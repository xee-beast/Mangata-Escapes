<?php

use App\Models\TravelAgent;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddEmailColumnToTravelAgentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('travel_agents', function (Blueprint $table) {
            $table->string('email')->nullable()->after('last_name');
        });

        TravelAgent::with('user')->get()->each(function ($agent) {
            $agent->update([
                'email' => $agent->user->email,
            ]);
        });

        Schema::table('travel_agents', function (Blueprint $table) {
            $table->string('email')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('travel_agents', function (Blueprint $table) {
            $table->dropColumn('email');
        });
    }
}

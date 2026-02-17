<?php

use App\Models\Client;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnsToClientsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->string('last_name')->nullable()->after('id');
            $table->string('first_name')->nullable()->after('id');
        });

        foreach (Client::with('bookings')->get() as $client) {
            if ($client->bookings->count()) {
                $client->update([
                    'first_name' => $client->bookings->first()->first_name,
                    'last_name' => $client->bookings->first()->last_name
                ]);
            } else {
                $client->update([
                    'first_name' =>  '',
                    'last_name' => ''
                ]);
            }
        }

        Schema::table('clients', function (Blueprint $table) {
            $table->string('last_name')->nullable(false)->change();
            $table->string('first_name')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn(['first_name', 'last_name']);
        });
    }
}

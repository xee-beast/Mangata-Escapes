<?php

use App\Models\Client;
use Illuminate\Database\Migrations\Migration;

class ConvertClientsTableEmailColumnToLowercase extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Client::all()->each(function ($client) {
            $client->update([
                'email' => strtolower(trim($client->email))
            ]);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Do nothing
    }
}

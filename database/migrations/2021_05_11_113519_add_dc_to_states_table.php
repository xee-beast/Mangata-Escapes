<?php

use App\Models\State;
use Illuminate\Database\Migrations\Migration;

class AddDcToStatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        State::create([
            'country_id' => 1,
            'name' => 'District of Columbia',
            'abbreviation' => 'DC'
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        State::where('country_id', 1)->where('abbreviation', 'DC')->delete();
    }
}

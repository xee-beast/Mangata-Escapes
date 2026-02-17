<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\ChildRate;
use Illuminate\Support\Str;

class AddUuidFieldChildRatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('child_rates', function (Blueprint $table) {
            $table->uuid('uuid')->nullable()->after('id');
        });

        $child_rates = ChildRate::all();

        foreach($child_rates as $child_rate) {
            $child_rate->uuid = (string) Str::uuid();
            $child_rate->save();
        }
    } 

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('child_rates', function (Blueprint $table) {
            $table->dropColumn('uuid');
        });
    }
}

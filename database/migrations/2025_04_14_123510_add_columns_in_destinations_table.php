<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnsInDestinationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('destinations', function (Blueprint $table) {
            $table->string('currency_description')->after('outlet_adapter')->nullable();
            $table->string('language_description')->after('outlet_adapter')->nullable();
            $table->text('tax_description')->after('outlet_adapter')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('destinations', function (Blueprint $table) {
            $table->dropColumn('currency_description');
            $table->dropColumn('language_description');
            $table->dropColumn('tax_description');
        });
    }
}

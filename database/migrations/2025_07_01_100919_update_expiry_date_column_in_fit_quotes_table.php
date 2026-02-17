<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateExpiryDateColumnInFitQuotesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('fit_quotes', function (Blueprint $table) {
            Schema::table('fit_quotes', function (Blueprint $table) {
                $table->renameColumn('expiry_date', 'expiry_date_time');
            });

            DB::statement("ALTER TABLE fit_quotes MODIFY expiry_date_time TIMESTAMP NULL");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('fit_quotes', function (Blueprint $table) {
           Schema::table('fit_quotes', function (Blueprint $table) {
                $table->renameColumn('expiry_date_time', 'expiry_date');
            });

            Schema::table('fit_quotes', function (Blueprint $table) {
                $table->date('expiry_date')->change();
            });
        });
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddLastNamesToGroupsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('groups', function (Blueprint $table) {
            $table->renameColumn('bride', 'bride_first_name');
            $table->renameColumn('groom', 'groom_first_name');
        });

        Schema::table('groups', function (Blueprint $table) {
            $table->string('bride_first_name', 32)->change();
            $table->string('bride_last_name', 32)->after('bride_first_name')->nullable();
            $table->string('groom_first_name', 32)->change();
            $table->string('groom_last_name', 32)->after('groom_first_name')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('groups', function (Blueprint $table) {
            $table->dropColumn('groom_last_name');
            $table->string('groom_first_name')->change();
            $table->dropColumn('bride_last_name');
            $table->string('bride_first_name')->change();
        });

        Schema::table('groups', function (Blueprint $table) {
            $table->renameColumn('groom_first_name', 'groom');
            $table->renameColumn('bride_first_name', 'bride');
        });
    }
}

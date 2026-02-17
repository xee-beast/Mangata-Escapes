<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RemoveTransferIdFromGroupsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('groups', function (Blueprint $table) {
            if (Schema::hasColumn('groups', 'transfer_id')) {
                $table->dropForeign(['transfer_id']);
                $table->dropColumn('transfer_id');
            }
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
            $table->unsignedBigInteger('transfer_id')->nullable()->after('transportation');
            $table->foreign('transfer_id')->references('id')->on('transfers')->onDelete('cascade');
        });
    }
}

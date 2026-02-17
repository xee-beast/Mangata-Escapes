<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSpecialistIdInLeadProvidersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('lead_providers', function (Blueprint $table) {
            $table->dropColumn('assigned_specialist');
            $table->foreignId('specialist_id')->nullable()->after('id_at_provider')->constrained('specialists')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('lead_providers', function (Blueprint $table) {
            $table->dropForeign(['specialist_id']);
            $table->dropColumn('specialist_id');
            $table->string('assigned_specialist')->nullable()->after('id_at_provider');
        });
    }
}

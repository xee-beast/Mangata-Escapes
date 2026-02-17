<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddProposalDocumentColumnInLeadHotelsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('lead_hotels', function (Blueprint $table) {
            $table->uuid('proposal_document_id')->nullable()->after('received_on');
            $table->foreign('proposal_document_id')->references('id')->on('files');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('lead_hotels', function (Blueprint $table) {
            //
        });
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTravelDocsFieldsInHotelsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('hotels', function (Blueprint $table) {
            $table->uuid('travel_docs_image_three_id')->nullable()->after('hero_image_id');
            $table->foreign('travel_docs_image_three_id')->references('id')->on('images');
            $table->uuid('travel_docs_image_two_id')->nullable()->after('hero_image_id');
            $table->foreign('travel_docs_image_two_id')->references('id')->on('images');
            $table->uuid('travel_docs_cover_image_id')->nullable()->after('hero_image_id');
            $table->foreign('travel_docs_cover_image_id')->references('id')->on('images');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('hotels', function (Blueprint $table) {
            $table->dropForeign(['travel_docs_cover_image_id']);
            $table->dropColumn('travel_docs_cover_image_id');
            $table->dropForeign(['travel_docs_image_two_id']);
            $table->dropColumn('travel_docs_image_two_id');
            $table->dropForeign(['travel_docs_image_three_id']);
            $table->dropColumn('travel_docs_image_three_id');
        });
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGroupFaqsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::dropIfExists('faq_group');

        Schema::table('faqs', function (Blueprint $table) {
            $table->dropColumn('default');
        });

        Schema::create('group_faqs', function (Blueprint $table) {
            $table->id();
            $table->text('title');
            $table->text('description');
            $table->enum('type', ['static', 'dynamic'])->default('static');
            $table->unsignedInteger('group_id');  
            $table->foreign('group_id')->references('id')->on('groups')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::create('faq_group', function (Blueprint $table) {
            $table->unsignedBigInteger('faq_id');
            $table->foreign('faq_id')->references('id')->on('faqs')->onDelete('cascade');
            $table->unsignedInteger('group_id');  
            $table->foreign('group_id')->references('id')->on('groups')->onDelete('cascade');
        });

        Schema::table('faqs', function (Blueprint $table) {
            $table->boolean('default')->after('type')->default(false);
        });

        Schema::dropIfExists('group_faqs');
    }
}

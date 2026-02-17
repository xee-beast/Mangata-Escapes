<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeAttritionImagePathToForeignKey extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('groups', function (Blueprint $table) {
            // Drop the existing attrition_image_path column if it exists
            if (Schema::hasColumn('groups', 'attrition_image_path')) {
                $table->dropColumn('attrition_image_path');
            }
            
            // Add the new attrition_image_id column as foreign key if it doesn't exist
            if (!Schema::hasColumn('groups', 'attrition_image_id')) {
                $table->uuid('attrition_image_id')->nullable();
                $table->foreign('attrition_image_id')->references('id')->on('images')->onDelete('set null');
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

            // Drop the foreign key if it exists
            $foreignKeys = Schema::getConnection()
                ->getDoctrineSchemaManager()
                ->listTableForeignKeys('groups');
            
            foreach ($foreignKeys as $foreignKey) {
                if (in_array('attrition_image_id', $foreignKey->getColumns())) {
                    $table->dropForeign(['attrition_image_id']);
                    break;
                }
            }

            // Drop the column if it exists
            if (Schema::hasColumn('groups', 'attrition_image_id')) {
                $table->dropColumn('attrition_image_id');
            }
            
            // Restore the original attrition_image_path column if it doesn't exist
            if (!Schema::hasColumn('groups', 'attrition_image_path')) {
                $table->string('attrition_image_path')->nullable();
            }
        });
    }
}
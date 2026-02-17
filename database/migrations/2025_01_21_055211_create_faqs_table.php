<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class CreateFaqsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('faqs', function (Blueprint $table) {
            $table->id();
            $table->text('title');
            $table->text('description');
            $table->enum('type', ['static', 'dynamic'])->default('static');
            $table->timestamps();
        });
        
        // Create Faq Permissions
        Permission::firstOrCreate(
            ['name' => 'manage faqs'],
            ['description' => 'Can add, view, update and delete faqs']
        );

        // fetch super admin and assign permissions
        $superAdminRole = Role::where('name', 'super admin')->first();

        $superAdminRole->givePermissionTo([
            'manage faqs',
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('faqs');
    }
}

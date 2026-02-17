<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class CreateTransfersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transfers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('primary_phone_number');
            $table->string('secondary_phone_number_label')->nullable();
            $table->string('secondary_phone_number_value')->nullable();
            $table->string('whatsapp_number')->nullable();
            $table->text('missed_or_changed_flight');
            $table->text('arrival_procedure');
            $table->text('departure_procedure');
            $table->uuid('app_image_id')->nullable();
            $table->foreign('app_image_id')->references('id')->on('images');
            $table->string('app_link')->nullable();
            $table->timestamps();
        });

        // Create Transfer Permissions
        Permission::firstOrCreate(
            ['name' => 'manage transfers'],
            ['description' => 'Can add, view, update and delete transfers']
        );

        // fetch super admin and assign permissions
        $superAdminRole = Role::where('name', 'super admin')->first();

        $superAdminRole->givePermissionTo([
            'manage transfers',
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('transfers');
    }
}

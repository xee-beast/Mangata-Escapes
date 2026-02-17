<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class SeedNewPermissions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Update calendar permissions
        Permission::whereIn('name', ['create calendar event', 'view calendar event', 'update calendar event', 'delete calendar event'])->delete();

        Permission::firstOrCreate(
            ['name' => 'manage calendar'],
            ['description' => 'Can access and manage calendar and add custom events or view existing events.']
        );

        // Create Event Types Permissions
        Permission::firstOrCreate(
            ['name' => 'manage event types'],
            ['description' => 'Can add, view, update and delete event types']
        );

        // fetch super admin and assign permissions
        $superAdminRole = Role::where('name', 'super admin')->first();

        $superAdminRole->givePermissionTo([
            'manage calendar',
            'manage event types',
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}

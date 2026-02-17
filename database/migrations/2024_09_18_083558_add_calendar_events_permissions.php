<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AddCalendarEventsPermissions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Permission::firstOrCreate(
            ['name' => 'create calendar event'],
            ['description' => 'Can create calendar events.']
        );
        Permission::firstOrCreate(
            ['name' => 'view calendar event'],
            ['description' => 'Can view calendar events.']
        );
        Permission::firstOrCreate(
            ['name' => 'update calendar event'],
            ['description' => 'Can update calendar events.']
        );
        Permission::firstOrCreate(
            ['name' => 'delete calendar event'],
            ['description' => 'Can delete calendar events.']
        );

        // fetch super admin and assign permissions
        $superAdminRole = Role::where('name', 'super admin')->first();
        $superAdminRole->givePermissionTo([
            'create calendar event', 'view calendar event', 'update calendar event',
            'delete calendar event'
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

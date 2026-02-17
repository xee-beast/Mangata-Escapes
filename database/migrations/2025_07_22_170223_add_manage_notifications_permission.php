<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AddManageNotificationsPermission extends Migration
{
    /**
     * Setup the migration so that all cached permissions can be reset.
     */
    public function __construct()
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Create the new permission
        $permission = Permission::firstOrCreate(
            ['name' => 'manage notifications'],
            ['description' => 'Can manage email notifications and templates']
        );

        // Assign to super admin role
        $superAdminRole = Role::where('name', 'super admin')->first();
        if ($superAdminRole) {
            $superAdminRole->givePermissionTo($permission);
        }

        // Assign to admin role if it exists
        $adminRole = Role::where('name', 'admin')->first();
        if ($adminRole) {
            $adminRole->givePermissionTo($permission);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Remove permission from roles
        $permission = Permission::where('name', 'manage notifications')->first();
        
        if ($permission) {
            // Remove from roles
            $permission->roles()->detach();
            
            // Delete the permission
            $permission->delete();
        }
    }
}

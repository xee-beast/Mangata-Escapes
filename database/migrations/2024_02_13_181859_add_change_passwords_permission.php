<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AddChangePasswordsPermission extends Migration
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
        /**
         * Create Change Password Permission
         */
        Permission::create(['name' => 'change passwords', 'description' => 'Can change passwords.']);

        $role = Role::findByName('super admin');

        $role->givePermissionTo('change passwords');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $role = Role::findByName('super admin');

        $role->revokePermissionTo('change passwords');

        Permission::where(['name' => 'change passwords'])->delete();
    }
}

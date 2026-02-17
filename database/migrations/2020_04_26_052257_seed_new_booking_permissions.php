<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class SeedNewBookingPermissions extends Migration
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
        Permission::create(['name' => 'confirm bookings', 'description' => 'Can confirm bookings after they have been confirmed with the provider.']);

        Permission::create(['name' => 'create payments', 'description' => 'Can create payments manually.']);
        Permission::create(['name' => 'process payments', 'description' => 'Can process, confirm and cancel payments.']);

        Role::findByName('super admin')->givePermissionTo(['confirm bookings', 'create payments', 'process payments']);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Permission::whereIn('name', ['process payments', 'create payments', 'confirm bookings'])->delete();
    }
}

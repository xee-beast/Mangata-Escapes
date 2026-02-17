<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AddPermissions extends Migration
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
        Permission::where('name', 'update insurance rates')->first()->update([
            'name' => 'manage insurance rates',
            'description' => 'Can create, update & delete insurance rates.'
        ]);

        Permission::where('name', 'confirm bookings')->first()->update([
            'name' => 'process bookings'
        ]);

        Permission::create([
            'name' => 'manage bookings',
            'description' => 'Can create, update & delete bookings.'
        ]);

        Permission::create([
            'name' => 'manage clients',
            'description' => 'Can create, update & delete clients.'
        ]);

        Role::findByName('super admin')->syncPermissions(Permission::all());
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Permission::where('name', 'manage clients')->delete();

        Permission::where('name', 'manage bookings')->delete();

        Permission::where('name', 'process bookings')->first()->update([
            'name' => 'confirm bookings'
        ]);

        Permission::where('name', 'manage insurance rates')->first()->update([
            'name' => 'update insurance rates',
            'description' => 'Can update insurance rates linked to at least one group.'
        ]);
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AddTimezoneAndPermissionsInAirportsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('airports', function (Blueprint $table) {
            $table->string('timezone')->after('airport_code')->nullable();
        });

        Permission::firstOrCreate(
            ['name' => 'manage airports'],
            ['description' => 'Can add, view, update and delete airports']
        );

        $superAdminRole = Role::where('name', 'super admin')->first();

        $superAdminRole->givePermissionTo([
            'manage airports',
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('airports', function (Blueprint $table) {
            $table->dropColumn('timezone');
        });

        Permission::where('name', 'manage airports')->delete();
    }
}

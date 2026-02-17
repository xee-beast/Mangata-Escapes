<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class CreateLeadsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('travel_agent_id')->nullable();
            $table->foreign('travel_agent_id')->references('id')->on('travel_agents')->onDelete('set null');
            $table->timestamp('assigned_at')->nullable();
            $table->string('bride_first_name');
            $table->string('bride_last_name');
            $table->string('groom_first_name');
            $table->string('groom_last_name');
            $table->string('departure')->nullable();
            $table->string('phone');
            $table->boolean('text_agreement')->nullable()->default(false);
            $table->string('email');
            $table->text('destinations')->nullable();
            $table->date('wedding_date')->nullable();
            $table->string('status')->nullable()->default('Unassigned');
            $table->string('referral_source')->nullable();
            $table->text('facebook_group')->nullable();
            $table->string('referred_by')->nullable();
            $table->text('message')->nullable();
            $table->date('contract_sent_on')->nullable();
            $table->date('last_attempt')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        $permissions = [
            [
                'name' => 'view all leads',
                'description' => 'Can view any leads',
            ],
            [
                'name' => 'view own leads',
                'description' => 'Can view only assigned leads',
            ],
            [
                'name' => 'create leads',
                'description' => 'Can create new leads',
            ],
            [
                'name' => 'update all leads',
                'description' => 'Can update any leads',
            ],
            [
                'name' => 'update own leads',
                'description' => 'Can update only assigned leads',
            ],
            [
                'name' => 'delete leads',
                'description' => 'Can delete any leads',
            ],
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['name' => $permission['name']],
                ['description' => $permission['description']]
            );
        }

        $roles = Role::whereIn('name', ['super admin', 'Operations Manager'])->get();

        foreach ($roles as $role) {
            $role->givePermissionTo([
                'view all leads',
                'view own leads',
                'create leads',
                'update all leads',
                'update own leads',
                'delete leads',
            ]);
        }

        $travelAgentRole = Role::where('name', 'Travel Agent')->first();

        if ($travelAgentRole) {
            $travelAgentRole->givePermissionTo([
                'view own leads',
                'update own leads',
            ]);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('leads');
    }
}

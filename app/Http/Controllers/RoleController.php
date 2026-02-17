<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\ForbiddenRolesPermissions;
use App\Http\Requests\StoreRole;
use App\Http\Requests\UpdateRole;
use App\Http\Resources\PermissionResource;
use App\Http\Resources\RoleResource;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    use ForbiddenRolesPermissions;

    public function __construct()
    {
        $this->authorizeResource(Role::class, 'role');
    }

    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $roles = Role::query()->with('permissions');

        $search = $request->query('search', '');
        if (!empty($search)) {
            $roles->where('name', 'LIKE', '%' . $search . '%')
                ->orWhereHas('permissions', function ($query) use ($search) {
                    $query->where('name', 'LIKE', '%' . $search . '%');
                });
        }

        $roles->whereNotIn('name', $this->forbiddenRoles());

        return RoleResource::collection($roles->paginate($request->query('paginate', 10)))
            ->additional([
                'can' => [
                    'create' => $request->user()->can('create', Role::class),
                ],
            ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\StoreRole  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreRole $request)
    {
        $role = new Role;
        $role->guard_name = 'web';
        $role->name = $request->input('name');
        $role->description = $request->input('description');
        $role->save();

        return (new RoleResource($role))->response()->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     *
     * @param  \Spatie\Permission\Models\Role  $role
     * @return \Illuminate\Http\Response
     */
    public function show(Role $role)
    {
        return (new RoleResource($role->load('permissions')))->additional([
            'permissions' => PermissionResource::collection(Permission::whereNotIn('name', $this->forbiddenPermissions())->get())
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateRole  $request
     * @param  \Spatie\Permission\Models\Role  $role
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateRole $request, Role $role)
    {
        $role->name = $request->input('name');
        $role->description = $request->input('description');
        $role->save();

        return new RoleResource($role->load('permissions'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \Spatie\Permission\Models\Role  $role
     * @return \Illuminate\Http\Response
     */
    public function destroy(Role $role)
    {
        $role->delete();

        return response()->json()->setStatusCode(204);
    }

    /**
     * Sync the role's permissions.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Spatie\Permission\Models\Role  $role
     * @return \Illuminate\Http\Response
     */
    public function syncPermissions(Request $request, Role $role)
    {
        $this->authorize('update', $role);

        $request->validate([
                'permissions' => 'present|array|bail',
                'permissions.*' => 'string',
            ]);

        $unassignablePermissions = ['manage roles', 'manage employee roles', 'manage employee permissions'];

        $permissions = Permission::whereIn('name', $request->input('permissions'))
            ->whereNotIn('name', $this->forbiddenPermissions())
            ->get();

        $role->syncPermissions($permissions->pluck('name'));

        return PermissionResource::collection($permissions);
    }
}

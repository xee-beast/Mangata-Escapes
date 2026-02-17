<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\ForbiddenRolesPermissions;
use App\Http\Requests\StoreUser;
use App\Http\Requests\UpdateUser;
use App\Http\Requests\UpdateUserPassword;
use App\Http\Requests\UpdateUserPasswordByAdmin;
use App\Http\Requests\UpdateUserPermissions;
use App\Http\Requests\UpdateUserRoles;
use App\Http\Resources\UserResource;
use App\Http\Resources\RoleResource;
use App\Http\Resources\PermissionResource;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class UserController extends Controller
{
    use ForbiddenRolesPermissions;

    public function __construct()
    {
        $this->authorizeResource(User::class, 'user');
    }

    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $users = User::query()->with(['roles']);

        $users->where(function ($query) use ($request) {
            $query->whereHas('roles', function ($query) {
                    $query->whereNotIn('name', $this->forbiddenRoles());
                })
                ->orWhereDoesntHave('roles')
                ->orWhere('id', $request->user()->id);
        });

        $search = $request->query('search', false);
        if (!empty($search)) {
            $users->where(function ($query) use ($search) {
                $query->where(DB::raw("CONCAT(first_name, ' ', last_name)"), 'like', '%' . $search . '%')
                    ->orwhere('email', 'like', '%' . $search . '%')
                    ->orwhere('username', 'like', '%' . $search . '%');
            });
        }

        $role = $request->query('role', false);
        if (!empty($role)) {
            $users->whereHas('roles', function ($query) use ($role) {
                $query->where('id', $role);
            });
        }

        $users->orderBy('first_name');

        return UserResource::collection($users->paginate($request->query('paginate', 10)))
                ->additional([
                'roles' => RoleResource::collection(Role::where('name', '!=', 'super admin')->get()),
                'can' => [
                    'create' => $request->user()->can('create', User::class),
                ]
            ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\StoreUser  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreUser $request)
    {
        $user = User::create([
            'first_name' => $request->input('firstName'),
            'last_name' => $request->input('lastName'),
            'username' => $request->input('username'),
            'email' => $request->input('email'),
            'password' => Hash::make($request->input('password')),
        ]);

        //To avoid sending the email verification notifications
        $user->markEmailAsVerified();

        event(new \App\Events\UserCreated($user, $request->input('password')));

        return (new UserResource($user))->response()->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\Response
     */
    public function show(User $user)
    {
        return (new UserResource($user->loadMissing(['roles.permissions', 'permissions'])))->additional([
            'roles' => RoleResource::collection(Role::with('permissions')->whereNotIn('name', $this->forbiddenRoles())->get()),
            'permissions' => PermissionResource::collection(Permission::whereNotIn('name', $this->forbiddenPermissions())->get()),
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\User  $user
     * @param  \App\Http\Requests\UpdateUser  $request
     * @return \Illuminate\Http\Response
     */
    public function update(User $user, UpdateUser $request)
    {
        $user->first_name = $request->input('firstName');
        $user->last_name = $request->input('lastName');
        $user->email = $request->input('email');
        $user->save();
        
        $user->markEmailAsVerified();

        return new UserResource($user);
    }

    /**
     * Delete the specified resource.
     *
     * @param  \App\User  $user
     * @return \Illuminate\Http\Response
     */
    public function destroy(User $user)
    {
        $user->delete();

        return response()->json()->setStatusCode(204);
    }

    /**
     * Sync the user's roles.
     *
     * @param  \App\User  $user
     * @param  \App\Http\Requests\UpdateUserRoles  $request
     * @return \Illuminate\Http\Response
     */
    public function syncRoles(User $user, UpdateUserRoles $request)
    {
        $roles = Role::whereIn('name', $request->input('roles'))->whereNotIn('name', $this->forbiddenRoles())->get();

        $user->syncRoles($roles->pluck('name'));

        return RoleResource::collection($roles->loadMissing('permissions'));
    }

    /**
     * Sync the employee's permissions.
     *
     * @param  \App\User $user
     * @param  \App\Http\Requests\UpdateUserPermissions  $request
     * @return \Illuminate\Http\Response
     */
    public function syncPermissions(User $user, UpdateUserPermissions $request)
    {
        $unassignablePermissions = ['manage roles', 'manage employee roles', 'manage employee permissions'];

        $permissions = Permission::whereIn('name', $request->input('permissions'))
            ->whereNotIn('name', $this->forbiddenPermissions())
            ->whereNotIn('name', $user->getPermissionsViaRoles()->diff($user->getDirectPermissions())->pluck('name')->all())
            ->get();

        $user->syncPermissions($permissions->pluck('name'));

        return PermissionResource::collection($permissions);
    }

    /**
     * Changes the authenticated users password.
     *
     * @param  \App\Http\Requests\UpdateUserPassword  $request
     * @return \Illuminate\Http\Response
     */
     public function changePassword(UpdateUserPassword $request) {
        $user = $request->user();
        $user->password = Hash::make($request->input('newPassword'));
        $user->save();

        return response()->json()->setStatusCode(204);
     }

     public function updatePasswordByAdmin(User $user, UpdateUserPasswordByAdmin $request) {

        $user->password = Hash::make($request->input('password'));
        $user->save();

        event(new \App\Events\PasswordChanged($user, $request->input('password')));

        return response()->json()->setStatusCode(204);
     }
}

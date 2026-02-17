<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'firstName' => $this->first_name,
            'lastName' => $this->last_name,
            'username' => $this->username,
            'email' => $this->email,
            'verifiedAt' => $this->email_verified_at,
            'roles' => RoleResource::collection($this->whenLoaded('roles')),
            'permissions' => PermissionResource::collection($this->whenLoaded('permissions')),
            'can' => [
                    'view' => auth()->user()->can('view', $this->resource),
                    'update' => auth()->user()->can('update', $this->resource),
                    'delete' => auth()->user()->can('delete', $this->resource),
                    'manageRoles' => auth()->user()->can('syncRoles', $this->resource),
                    'managePermissions' => auth()->user()->can('syncPermissions', 
                    $this->resource),
                    'changePasswords' => auth()->user()->can('change passwords', $this->resource),
                ],
        ];
    }
}

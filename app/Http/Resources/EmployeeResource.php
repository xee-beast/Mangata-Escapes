<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeResource extends JsonResource
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
            'createdAt' => $this->created_at,
            'user' => new UserResource($this->whenLoaded('user')),
            'can' => [
                    'view' => auth()->user()->can('view', $this->resource),
                    'update' => auth()->user()->can('update', $this->resource),
                    'delete' => auth()->user()->can('delete', $this->resource),
                    'manageRoles' => auth()->user()->can('syncRoles', $this->resource),
                    'managePermissions' => auth()->user()->can('syncPermissions', $this->resource),
                    'changeEmail' => auth()->user()->can('changeEmail', $this->resource),
                ],
        ];
    }
}

<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class BrandResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'concessions' => $this->concessions,
            'can' => [
                'view' => auth()->user()->can('manage brands'),
                'update' => auth()->user()->can('manage brands'),
                'delete' => auth()->user()->can('manage brands'),
            ]
        ];
    }
}

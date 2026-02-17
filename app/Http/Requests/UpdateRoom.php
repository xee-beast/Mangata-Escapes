<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRoom extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $rules = [
            'name' => ['required', 'string', 'max:100', Rule::unique('rooms', 'name')->where('hotel_id', $this->hotel->id)->ignore($this->route('room'))],
            'description' => 'nullable|string',
            'size' => 'required|string|max:100',
            'view' => 'required|string|max:100',
            'image' => 'nullable|array',
            'image.uuid' => 'required_with:image|uuid',
            'image.path' => 'required_with:image|string',
            'minOccupants' => 'required|integer|min:1|max:20|lte:maxOccupants',
            'maxOccupants' => 'required|integer|min:1|max:20|gte:minOccupants',
            'adultsOnly' => 'nullable|boolean',
        ];

        if (!$this->adultsOnly) {
            $rules = array_merge($rules, [
                'maxAdults' => 'required|integer|min:1|lte:maxOccupants',
                'maxChildren' => 'required|integer|min:1|lte:maxOccupants',
                'minAdultsPerChild' => 'required|integer|min:1',
                'maxChildrenPerAdult' => 'required|integer|min:1'
            ]);
        }

        return $rules;
    }
}

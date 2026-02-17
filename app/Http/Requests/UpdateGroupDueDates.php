<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateGroupDueDates extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'cancellationDate' => 'required|date|before:' . $this->group->event_date->format('m/d/Y'),
            'dueDate' => 'required|date|before:' . $this->group->event_date->format('m/d/Y'),
            'other' => 'nullable|array',
            'other.*' => 'required_with:other|array',
            'other.*.date' => 'required_with:other|date|before:' . $this->group->event_date->format('m/d/Y'),
            'other.*.type' => ['required_with:other', Rule::in($this->group->is_fit ? ['percentage', 'price'] : ['nights', 'percentage', 'price'])],
            'other.*.amount' => 'required_with:other|numeric|min:0',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array
     */
    public function attributes()
    {
        return [
            'other.*.date' => 'due date',
            'other.*.type' => 'type',
            'other.*.amount' => 'amount',
        ];
    }
}

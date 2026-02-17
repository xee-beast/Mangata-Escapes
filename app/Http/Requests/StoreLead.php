<?php

namespace App\Http\Requests;

use App\Models\Lead;
use Illuminate\Foundation\Http\FormRequest;

class StoreLead extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return $this->user()->can('create', Lead::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'brideFirstName' => ['nullable', 'required_with:brideLastName', 'string', 'max:255'],
            'brideLastName' => ['nullable', 'required_with:brideFirstName', 'string', 'max:255'],
            'groomFirstName' => ['nullable', 'required_with:groomLastName', 'string', 'max:255'],
            'groomLastName' => ['nullable', 'required_with:groomFirstName', 'string', 'max:255'],            
            'departure' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'numeric', 'digits_between:7,12'],
            'textAgreement' => ['nullable', 'boolean'],
            'email' => ['required', 'email', 'unique:leads,email', 'max:255'],
            'destinations' => ['nullable', 'string', 'max:5000'],
            'weddingDate' => ['nullable', 'date', 'after:today'],
            'travelAgentRequested' => ['nullable', 'string', 'max:255'],
            'referralSource' => ['nullable', 'string', 'max:255'],
            'facebookGroup' => ['nullable', 'string', 'max:5000'],
            'referredBy' => ['nullable', 'string', 'max:255'],
            'message' => ['nullable', 'string', 'max:5000'],
            'contactedUsBy' => ['nullable', 'string', 'max:255'],
            'contactedUsDate' => ['nullable', 'date', 'before_or_equal:today'],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $hasBride = $this->filled('brideFirstName') && $this->filled('brideLastName');
            $hasGroom = $this->filled('groomFirstName') && $this->filled('groomLastName');

            if (!$hasBride && !$hasGroom) {
                $fields = ['brideFirstName', 'brideLastName', 'groomFirstName', 'groomLastName'];
                $message = 'Please provide both the first and last name for either the bride or the groom.';

                foreach ($fields as $field) {
                    $validator->errors()->add($field, $message);
                }
            }
        });
    }
}

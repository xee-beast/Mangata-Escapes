<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SendSupplierEmail extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'email' => ['required', 'email', 'max:255'],
            'cc' => [
                'nullable',
                function ($attribute, $value, $fail) {
                    $emails = array_map('trim', explode(',', $value));

                    foreach ($emails as $email) {
                        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                            $fail("The $attribute field contains an invalid email: $email");
                        }
                    }
                }
            ],
            'body' => ['required', 'string', 'max:5000'],
            'supplierIdentifier' => ['nullable', 'string', 'max:50'],
        ];
    }

    public function attributes()
    {
        return [
            'email' => 'to'
        ];
    }
}

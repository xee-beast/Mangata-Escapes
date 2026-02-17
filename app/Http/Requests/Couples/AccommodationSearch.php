<?php

namespace App\Http\Requests\Couples;

use Illuminate\Foundation\Http\FormRequest;
use Carbon\Carbon;

class AccommodationSearch extends FormRequest
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
            'checkIn' => ['required', 'date', 'bail', 'before:checkOut'],
            'checkOut' => [
                'required', 'date', 'bail', 'after:checkIn', 
                function ($attribute, $value, $fail) { 
                    if (Carbon::parse($this->input('checkIn'))->diffInDays($value) < $this->group->min_nights) { 
                        $fail("You must book for a minimum of {$this->group->min_nights} nights."); 
                    } 
                }
            ],
            'adults' => ['required', 'integer', 'min:1'],
            'children' => ['required', 'integer', 'min:0'],
            'birthDates' => ['nullable', 'array'],
            'birthDates.*' => ['required', 'date', 
                function($attribute, $value, $fail) {
                    if (Carbon::parse($value)->diffInYears(Carbon::now()) >= 18) { 
                        $fail("Your children must be under 18 years old."); 
                    } 
                }
            ],
        ];
    }

    public function messages()
    {
        return [
            'checkIn.required' => 'Please select travel dates.',
            'checkOut.required' => 'Please select travel dates.',
            'adults.min' => 'Please have at least one adult.',
            'children.min' => 'Children cannot be negative.',
            'birthDates.*.required' => 'Please select a birth date for this child.'
        ];
    }    
}
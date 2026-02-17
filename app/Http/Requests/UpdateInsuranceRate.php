<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateInsuranceRate extends FormRequest
{
    /**
     * Override the validator instance for the request.
     *
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function getValidatorInstance()
    {
        $data = $this->all();
        $data['url'] = ((!is_null($data['url'])) && (strpos($data['url'], 'http') !== 0)) ? 'http://' . $data['url'] : $data['url'];
        $this->getInputSource()->replace($data);
        
        $validator = parent::getValidatorInstance();

        $validator->after(function ($validator) {
            foreach ($this->rates as $index => $rate) {

                if ($validator->errors()->has('rates.' . $index . '.to')) {
                    break;
                }

                if ($rate['to'] <= ($index ? $this->rates[$index - 1]['to'] : 0)) {
                    $validator->errors()->add('rates.' . $index . '.to', 'To must be greater than ' . ($index ? $this->rates[$index - 1]['to'] : 0) . '.');
                }
            }
        });

        return $validator;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => ['required', 'string', Rule::unique('insurance_rates', 'name')->where('provider_id', $this->provider->id)->ignore($this->route('insuranceRate'))],
            'description' => ['nullable', 'string'],
            'startDate' => ['nullable', 'date'],
            'calculateBy' => 'required|in:total,nights',
            'rates' => 'required|array|min:1',
            'rates.*.to' => 'required|numeric|min:0',
            'rates.*.rate' => 'required|numeric|min:0',
            'url' => 'nullable|url'
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
            'rates.*.to' => 'to',
            'rates.*.rate' => 'rate',
        ];
    }
}

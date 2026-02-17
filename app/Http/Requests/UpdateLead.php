<?php

namespace App\Http\Requests;

use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;

class UpdateLead extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return $this->user()->can('update', $this->route('lead'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'isFit' => ['nullable', 'boolean'],
            'isCanadian' => ['nullable', 'boolean'],
            'travelAgentId' => ['nullable', 'exists:travel_agents,id'],
            'brideFirstName' => ['nullable', 'required_with:brideLastName', 'string', 'max:255'],
            'brideLastName' => ['nullable', 'required_with:brideFirstName', 'string', 'max:255'],
            'groomFirstName' => ['nullable', 'required_with:groomLastName', 'string', 'max:255'],
            'groomLastName' => ['nullable', 'required_with:groomFirstName', 'string', 'max:255'],
            'departure' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'numeric', 'digits_between:7,12'],
            'textAgreement' => ['nullable', 'boolean'],
            'email' => ['required', 'email', 'unique:leads,email,' . $this->route('lead')->id . ',id', 'max:255'],
            'venue' => ['nullable', 'string', 'max:255'],
            'site' => ['nullable', 'string', 'max:255', 'in:Unknown,On-site,Off-site'],
            'numberOfPeople' => ['nullable', 'integer', 'max:1000'],
            'numberOfRooms' => ['nullable', 'integer', 'max:1000'],
            'destinations' => ['nullable', 'string', 'max:5000'],
            'weddingDate' => ['nullable', 'date', 'after:contactedUsDate'],
            'weddingDateConfirmed' => ['nullable', 'boolean'],
            'travelStartDate' => [
                'nullable',
                'required_with:travelEndDate',
                'date',
                function ($attribute, $value, $fail) {
                    $weddingDate = $this->input('weddingDate');

                    if ($weddingDate) {
                        $startLimit = Carbon::parse($weddingDate)->subDays(10);
                        $endLimit = Carbon::parse($weddingDate);
                        $travelStartDate = Carbon::parse($value);

                        if ($travelStartDate->lt($startLimit) || $travelStartDate->gt($endLimit)) {
                            $fail("Travel start date must be within 10 days before the wedding date.");
                        }
                    }
                },
            ],
            'travelEndDate' => [
                'nullable',
                'required_with:travelStartDate',
                'date',
                function ($attribute, $value, $fail) {
                    $weddingDate = $this->input('weddingDate');

                    if ($weddingDate) {
                        $startLimit = Carbon::parse($weddingDate);
                        $endLimit = Carbon::parse($weddingDate)->addDays(10);
                        $travelEndDate = Carbon::parse($value);

                        if ($travelEndDate->lt($startLimit) || $travelEndDate->gt($endLimit)) {
                            $fail("Travel end date must be within 10 days after the wedding date.");
                        }
                    }
                },
            ],
            'status' => ['required', 'string', 'in:Unassigned,Assigned,Pending Rates,Received Rates,Pending K,Pending Deposit,Signed K,Declined'],
            'travelAgentRequested' => ['nullable', 'string', 'max:255'],
            'referralSource' => ['nullable', 'string', 'max:255'],
            'facebookGroup' => ['nullable', 'string', 'max:5000'],
            'referredBy' => ['nullable', 'string', 'max:255'],
            'message' => ['nullable', 'string', 'max:5000'],
            'contractSentOn' => ['nullable', 'date', 'before:weddingDate'],
            'lastAttempt' => ['nullable', 'date', 'before:weddingDate'],
            'respondedOn' => ['nullable', 'date', 'before:weddingDate'],
            'releaseRoomsBy' => [
                'nullable',
                'date',
                function ($attribute, $value, $fail) {
                    $weddingDate = $this->input('weddingDate');

                    if ($weddingDate) {
                        $startLimit = Carbon::parse($weddingDate);
                        $endLimit = Carbon::parse($weddingDate)->addDays(10);
                        $travelEndDate = Carbon::parse($value);

                        if ($travelEndDate->lt($startLimit) || $travelEndDate->gt($endLimit)) {
                            $fail("Release rooms by date must be within 10 days after the wedding date.");
                        }
                    }
                },
            ],
            'balanceDueDate' => ['nullable', 'date', 'before:weddingDate'],
            'cancellationDate' => ['nullable', 'date', 'before:weddingDate'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'contactedUsBy' => ['nullable', 'string', 'max:255'],
            'contactedUsDate' => ['required', 'date', 'before_or_equal:today'],
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

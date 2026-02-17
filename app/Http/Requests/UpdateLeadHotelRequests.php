<?php

namespace App\Http\Requests;

use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;

class UpdateLeadHotelRequests extends FormRequest
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
        $lead = $this->route('lead');

        return [
            'leadProviders' => ['nullable', 'array'],
            'leadProviders.*.providerId' => ['required', 'exists:providers,id'],
            'leadProviders.*.idAtProvider' => ['nullable', 'string', 'max:255'],
            'leadProviders.*.specialistId' => ['nullable', 'exists:specialists,id'],
            'leadProviders.*.leadHotels' => ['nullable', 'array'],
            'leadProviders.*.leadHotels.*.hotel' => ['required', 'string', 'max:255'],
            'leadProviders.*.leadHotels.*.brandId' => ['nullable', 'exists:brands,id'],
            'leadProviders.*.leadHotels.*.requestedOn' => ['required', 'date', 'before_or_equal:today'],
            'leadProviders.*.leadHotels.*.weddingDate' => ['nullable', 'date',  'after:' . $lead->contacted_us_date->format('Y-m-d')],
            'leadProviders.*.leadHotels.*.travelStartDate' => [
                'nullable',
                'required_with:leadProviders.*.leadHotels.*.travelEndDate',
                'date',
                function ($attribute, $value, $fail) {
                    $weddingKey = str_replace('travelStartDate', 'weddingDate', $attribute);
                    $weddingDate = request()->input($weddingKey);

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
            'leadProviders.*.leadHotels.*.travelEndDate' => [
                'nullable',
                'required_with:leadProviders.*.leadHotels.*.travelStartDate',
                'date',
                function ($attribute, $value, $fail) {
                    $weddingKey = str_replace('travelEndDate', 'weddingDate', $attribute);
                    $weddingDate = request()->input($weddingKey);

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
            'leadProviders.*.leadHotels.*.receivedOn' => ['nullable', 'required_with:leadProviders.*.leadHotels.*.proposalDocument', 'date', 'before_or_equal:today'],
            'leadProviders.*.leadHotels.*.proposalDocument' => ['nullable', 'required_with:leadProviders.*.leadHotels.*.receivedOn', 'array'],
            'leadProviders.*.leadHotels.*.proposalDocument.uuid' => ['required_with:leadProviders.*.leadHotels.*.proposalDocument', 'uuid'],
            'leadProviders.*.leadHotels.*.proposalDocument.path' => ['required_with:leadProviders.*.leadHotels.*.proposalDocument', 'string'],
        ];
    }

    public function attributes()
    {
        return [
            'leadProviders' => 'hotel requests',
            'leadProviders.*.providerId' => 'supplier',
            'leadProviders.*.idAtProvider' => 'group id',
            'leadProviders.*.specialistId' => 'specialist',
            'leadProviders.*.leadHotels' => 'hotels',
            'leadProviders.*.leadHotels.*.hotel' => 'hotel',
            'leadProviders.*.leadHotels.*.brandId' => 'brand',
            'leadProviders.*.leadHotels.*.requestedOn' => 'requested on',
            'leadProviders.*.leadHotels.*.weddingDate' => 'wedding date',
            'leadProviders.*.leadHotels.*.travelStartDate' => 'travel start date',
            'leadProviders.*.leadHotels.*.travelEndDate' => 'travel end date',
            'leadProviders.*.leadHotels.*.receivedOn' => 'received on',
            'leadProviders.*.leadHotels.*.proposalDocument' => 'proposal document',
            'leadProviders.*.leadHotels.*.proposalDocument.uuid' => 'proposal document',
            'leadProviders.*.leadHotels.*.proposalDocument.path' => 'proposal document',
        ];
    }
}

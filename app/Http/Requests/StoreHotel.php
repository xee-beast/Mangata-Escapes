<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreHotel extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => 'required|string|max:50',
            'destination' => 'required|numeric|exists:destinations,id',
            'description' => 'nullable|string',
            'url' => 'nullable|url',
            'travelDocsCoverImage' => 'nullable|array',
            'travelDocsCoverImage.uuid' => 'required_with:travelDocsCoverImage|uuid',
            'travelDocsCoverImage.path' => 'required_with:travelDocsCoverImage|string',
            'travelDocsImageTwo' => 'nullable|array',
            'travelDocsImageTwo.uuid' => 'required_with:travelDocsImageTwo|uuid',
            'travelDocsImageTwo.path' => 'required_with:travelDocsImageTwo|string',
            'travelDocsImageThree' => 'nullable|array',
            'travelDocsImageThree.uuid' => 'required_with:travelDocsImageThree|uuid',
            'travelDocsImageThree.path' => 'required_with:travelDocsImageThree|string',
            'images' => 'nullable|array',
            'images.*.uuid' => 'required_with:images|uuid',
            'images.*.path' => 'required_with:images|string',
        ];
    }
}

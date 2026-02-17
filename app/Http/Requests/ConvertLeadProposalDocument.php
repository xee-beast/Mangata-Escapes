<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ConvertLeadProposalDocument extends FormRequest
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
            'id' => 'required|exists:lead_hotels,id',
            'hotel' => 'required|string',
            'brand' => 'required|array',
            'brand.id' => 'required|exists:brands,id',
            'brand.name' => 'required|string',
            'brand.concessions' => 'required|string',
            'brandId' => 'required|exists:brands,id',
            'requestedOn' => 'required|date',
            'weddingDate' => 'required|date',
            'travelStartDate' => 'required|date',
            'travelEndDate' => 'required|date',
            'receivedOn' => 'required|date',
            'proposalDocument' => 'required|array',
            'proposalDocument.uuid' => 'required|string',
            'proposalDocument.name' => 'required|string',
            'proposalDocument.mime_type' => 'required|string|in:application/pdf',
            'proposalDocument.path' => 'required|string',
            'proposalDocument.storagePath' => 'required|string',
        ];
    }

    public function attributes()
    {
        return [
            'brand.id' => 'brand ID',
            'brand.name' => 'brand name',
            'brand.concessions' => 'brand concessions',
            'brandId' => 'brand ID',
            'requestedOn' => 'requested on date',
            'weddingDate' => 'wedding date',
            'travelStartDate' => 'travel start date',
            'travelEndDate' => 'travel end date',
            'receivedOn' => 'received on date',
            'proposalDocument' => 'proposal document',
            'proposalDocument.uuid' => 'proposal document UUID',
            'proposalDocument.name' => 'proposal document name',
            'proposalDocument.mime_type' => 'proposal document MIME type',
            'proposalDocument.path' => 'proposal document path',
            'proposalDocument.storagePath' => 'proposal document storage path',
        ];
    }
}

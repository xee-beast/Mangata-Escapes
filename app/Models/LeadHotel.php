<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeadHotel extends Model
{
    use HasFactory;

    protected $fillable = [
        'lead_provider_id',
        'brand_id',
        'hotel',
        'requested_on',
        'wedding_date',
        'travel_start_date',
        'travel_end_date',
        'received_on',
        'proposal_document_id',
    ];

    protected $casts = [
        'requested_on' => 'date',
        'wedding_date' => 'date',
        'travel_start_date' => 'date',
        'travel_end_date' => 'date',
        'received_on' => 'date',
    ];

    public function leadProvider()
    {
        return $this->belongsTo(LeadProvider::class);
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function proposal_document()
    {
        return $this->belongsTo(File::class);
    }
}

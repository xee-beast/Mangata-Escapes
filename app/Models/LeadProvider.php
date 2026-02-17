<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeadProvider extends Model
{
    use HasFactory;

     protected $fillable = [
        'lead_id',
        'provider_id',
        'id_at_provider',
        'specialist_id',
    ];

    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }

    public function provider()
    {
        return $this->belongsTo(Provider::class);
    }

    public function specialist()
    {
        return $this->belongsTo(Specialist::class);
    }

    public function leadHotels()
    {
        return $this->hasMany(LeadHotel::class);
    }

}

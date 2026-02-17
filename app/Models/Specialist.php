<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Specialist extends Model
{
    use HasFactory;

    protected $fillable = [
        'provider_id',
        'name',
        'email',
    ];

    public function provider()
    {
        return $this->belongsTo(Provider::class);
    }

    public function leadProviders()
    {
        return $this->hasMany(LeadProvider::class);
    }
}

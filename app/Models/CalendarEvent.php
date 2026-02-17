<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CalendarEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'color', 'is_default',
    ];

    public function events() {
        return $this->hasMany(Event::class);
    }
}

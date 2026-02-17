<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BookingDueDate extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = ['booking_id', 'date', 'amount', 'type'];

    protected $dates = ['date'];

    public function getKeyAttribute()
    {
        $key = '';
        
        if ($this->type == 'nights') {
            $key .= intval($this->amount) . ' nights';
        } else if ($this->type == 'percentage') {
            $key .= intval($this->amount) . '%';
        } else if ($this->type == 'price') {
            $key .= '$' . $this->amount;
        }

        return $key . ' due date';
    }

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }
}

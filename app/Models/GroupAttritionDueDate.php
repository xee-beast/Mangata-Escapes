<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GroupAttritionDueDate extends Model
{
    use HasFactory;

    protected $dates = [
        'date',
    ];

    protected $fillable = ['date', 'group_id'];

    public function setDateAttribute($value)
    {
        $this->attributes['date'] = Carbon::parse($value);
    }

    public function group()
    {
        return $this->belongsTo(Group::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class GuestChange extends Model
{
    use SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = [
        'snapshot' => 'array',
    ];

    public function bookingClient()
    {
        return $this->belongsTo(BookingClient::class);
    }

    public static function snapshot(Model $model)
    {
        $snapshot = $model->getAttributes();

        foreach ($model->getRelations() as $relation => $related) {
            $snapshot[$relation] = $related instanceof Model
                ? static::snapshot($related)
                : (
                    $related
                    ? $related->map(function ($related) {
                        return static::snapshot($related);
                    })->toArray()
                    : []
                );
        }

        return $snapshot;
    }
}

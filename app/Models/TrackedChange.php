<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class TrackedChange extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'snapshot' => 'array',
    ];

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

    public static function somethingChanged($values, $before, $after)
    {
        return collect($values)->contains(function ($value, $key) use ($before, $after) {
            if (is_string($key) && is_array($value)) {
                $beforeRelation = collect($before[$key] ?? []);
                $afterRelation = collect($after[$key] ?? []);

                if (
                    $beforeRelation->contains(function ($value, $key) {return is_string($key);}) ||
                    $afterRelation->contains(function ($value, $key) {return is_string($key);})
                ) {
                    return static::somethingChanged($value, $beforeRelation->toArray(), $afterRelation->toArray());
                }

                return Collection::times(max($beforeRelation->count(), $afterRelation->count()))->contains(function ($index) use ($value, $beforeRelation, $afterRelation) {
                    return static::somethingChanged($value, $beforeRelation->get($index - 1) ?? [], $afterRelation->get($index - 1) ?? []);
                });
            }

            return ($before[$value] ?? null) !== ($after[$value] ?? null);
        });
    }
}

<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;

trait BelongsToPharmacy
{
    protected static function bootBelongsToPharmacy()
    {
        static::addGlobalScope('pharmacy', function (Builder $builder) {
            if (auth()->check() && !auth()->user()->hasRole('super-admin')) {
                if (auth()->user()->pharmacy_id) {
                    $builder->where($builder->getModel()->getTable() . '.pharmacy_id', auth()->user()->pharmacy_id);
                } else {
                    $builder->whereRaw('1 = 0');
                }
            }
        });

        static::creating(function ($model) {
            if (auth()->check() && auth()->user()->pharmacy_id && !auth()->user()->hasRole('super-admin')) {
                $model->pharmacy_id = auth()->user()->pharmacy_id;
            }
        });
    }

    public function pharmacy()
    {
        return $this->belongsTo(Pharmacy::class);
    }
}
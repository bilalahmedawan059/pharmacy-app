<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    protected $fillable = ['name', 'guard_name', 'pharmacy_id'];

    protected static function booted()
    {
        static::addGlobalScope('pharmacy-role', function (Builder $builder) {
            if (auth()->check() && !DB::table('model_has_roles')
                ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
                ->where('model_has_roles.model_id', auth()->id())
                ->where('model_has_roles.model_type', User::class)
                ->where('roles.name', 'super-admin')
                ->exists()) {
                $builder->where(function (Builder $query) {
                    $query->whereNull($query->getModel()->getTable() . '.pharmacy_id')
                        ->orWhere($query->getModel()->getTable() . '.pharmacy_id', auth()->user()->pharmacy_id);
                });
            }
        });

        static::creating(function ($role) {
            if (auth()->check() && auth()->user()->pharmacy_id) {
                $role->pharmacy_id = auth()->user()->pharmacy_id;
            }
        });
    }

    public function pharmacy()
    {
        return $this->belongsTo(Pharmacy::class);
    }
}
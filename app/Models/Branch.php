<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    protected $fillable = [
        'pharmacy_id', 'name', 'address', 'city', 'contact', 'license_number', 'status',
    ];

    public function pharmacy()
    {
        return $this->belongsTo(Pharmacy::class);
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }
}
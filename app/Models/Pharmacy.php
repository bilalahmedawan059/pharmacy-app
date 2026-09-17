<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pharmacy extends Model
{
    protected $fillable = [
        'business_name', 'owner_name', 'owner_email', 'tax_type', 'tax_id', 'address',
        'contact_address', 'city', 'phone_number', 'status', 'launched_at',
        'is_manufacturing', 'max_employees',
    ];

    protected $casts = ['launched_at' => 'datetime'];

    public function branches()
    {
        return $this->hasMany(Branch::class);
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }
}
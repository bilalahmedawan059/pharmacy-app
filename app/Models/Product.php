<?php

namespace App\Models;

use App\Models\Category;
use App\Models\Purchase;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Concerns\BelongsToPharmacy;

class Product extends Model
{
    use HasFactory, SoftDeletes, Notifiable, BelongsToPharmacy;

    protected $fillable = [
        'purchase_id','price',
        'discount','description','product_code','pharmacy_id',
    ];



    public function purchase(){
        return $this->belongsTo(Purchase::class);
    }
}

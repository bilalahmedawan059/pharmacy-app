<?php

namespace App\Models;

use App\Models\Product;
use App\Models\Purchase;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Concerns\BelongsToPharmacy;

class Sales extends Model
{
    use HasFactory, SoftDeletes, BelongsToPharmacy;

    protected $fillable = [
        'sale_transaction_id','product_id','quantity','total_price','pharmacy_id',
    ];

    public function product(){
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function purchase(){
        return $this->belongsTo(Purchase::class,'purchase_id');
    }

    public function transaction(){
        return $this->belongsTo(SaleTransaction::class, 'sale_transaction_id');
    }
    
}

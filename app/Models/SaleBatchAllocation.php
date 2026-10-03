<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleBatchAllocation extends Model
{
    protected $fillable = [
        'sale_id',
        'batch_id',
        'quantity',
        'returned_quantity',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'returned_quantity' => 'integer',
    ];

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sales::class, 'sale_id');
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class, 'batch_id');
    }
}

<?php

namespace App\Models;

use App\Models\Concerns\BelongsToPharmacy;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Batch extends Model
{
    use BelongsToPharmacy;

    protected $fillable = [
        'purchase_id',
        'branch_id',
        'batch_number',
        'expiry_date',
        'quantity_received',
        'quantity_available',
        'pharmacy_id',
    ];

    protected $casts = [
        'expiry_date' => 'date',
        'quantity_received' => 'integer',
        'quantity_available' => 'integer',
    ];

    protected static function booted(): void
    {
        static::saved(function (self $batch) {
            if ($batch->purchase) {
                $batch->purchase->refreshTotals();
            }
        });

        static::deleted(function (self $batch) {
            if ($batch->purchase) {
                $batch->purchase->refreshTotals();
            }
        });
    }

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function saleAllocations(): HasMany
    {
        return $this->hasMany(SaleBatchAllocation::class, 'batch_id');
    }

    public function scopeAvailableForSale($query)
    {
        return $query->where('quantity_available', '>', 0)
            ->whereDate('expiry_date', '>=', Carbon::today());
    }

    public function scopeExpired($query)
    {
        return $query->whereDate('expiry_date', '<', Carbon::today());
    }
}

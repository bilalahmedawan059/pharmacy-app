<?php

namespace App\Models;

use App\Models\Category;
use App\Models\Supplier;
use App\Models\Concerns\BelongsToPharmacy;
use App\Events\ProductReachedLowStock;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Purchase extends Model
{
    use HasFactory, Notifiable, SoftDeletes, BelongsToPharmacy;

    protected $fillable = [
        'name', 'category_id', 'price', 'quantity',
        'image', 'expiry_date', 'supplier_id', 'pharmacy_id',
    ];

    protected $casts = [
        'expiry_date' => 'date',
        'quantity' => 'integer',
    ];

    protected static function booted(): void
    {
        static::created(function (self $purchase) {
            if ($purchase->batches()->count() === 0) {
                $purchase->createLegacyBatch();
            }
        });
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function batches()
    {
        return $this->hasMany(Batch::class);
    }

    public function createLegacyBatch(): Batch
    {
        $batchNumber = 'LEGACY-' . $this->id;

        return Batch::firstOrCreate([
            'purchase_id' => $this->id,
            'batch_number' => $batchNumber,
            'branch_id' => null,
        ], [
            'expiry_date' => $this->expiry_date ?: now()->endOfMonth()->toDateString(),
            'quantity_received' => (int) ($this->quantity ?? 0),
            'quantity_available' => (int) ($this->quantity ?? 0),
            'pharmacy_id' => $this->pharmacy_id,
        ]);
    }

    public function refreshTotals(): void
    {
        $totalQuantity = (int) $this->batches()->sum('quantity_available');
        $earliestExpiry = $this->batches()->where('quantity_available', '>', 0)->min('expiry_date');

        $this->quantity = $totalQuantity;
        $this->expiry_date = $earliestExpiry ?: $this->expiry_date;
        $this->saveQuietly();
    }
}

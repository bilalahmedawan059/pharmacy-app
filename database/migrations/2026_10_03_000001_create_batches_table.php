<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->string('batch_number');
            $table->date('expiry_date');
            $table->unsignedInteger('quantity_received')->default(0);
            $table->unsignedInteger('quantity_available')->default(0);
            $table->unsignedBigInteger('pharmacy_id')->nullable();
            $table->timestamps();

            $table->unique(['purchase_id', 'branch_id', 'batch_number'], 'purchase_branch_batch_unique');
        });

        $legacyPurchases = DB::table('purchases')->orderBy('id')->get();
        foreach ($legacyPurchases as $purchase) {
            $batchNumber = 'LEGACY-' . $purchase->id;
            $expiryDate = $purchase->expiry_date ?: now()->endOfMonth()->toDateString();

            if (!DB::table('batches')->where('purchase_id', $purchase->id)->where('batch_number', $batchNumber)->exists()) {
                DB::table('batches')->insert([
                    'purchase_id' => $purchase->id,
                    'branch_id' => null,
                    'batch_number' => $batchNumber,
                    'expiry_date' => $expiryDate,
                    'quantity_received' => (int) ($purchase->quantity ?? 0),
                    'quantity_available' => (int) ($purchase->quantity ?? 0),
                    'pharmacy_id' => $purchase->pharmacy_id ?? null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        $purchases = DB::table('purchases')->get();
        foreach ($purchases as $purchase) {
            $total = (int) DB::table('batches')->where('purchase_id', $purchase->id)->sum('quantity_available');
            $earliestExpiry = DB::table('batches')->where('purchase_id', $purchase->id)->where('quantity_available', '>', 0)->min('expiry_date');

            DB::table('purchases')->where('id', $purchase->id)->update([
                'quantity' => $total,
                'expiry_date' => $earliestExpiry ?: $purchase->expiry_date,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('batches');
    }
};

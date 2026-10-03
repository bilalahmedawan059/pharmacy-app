<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sale_batch_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained('sales')->cascadeOnDelete();
            $table->foreignId('batch_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('quantity')->default(0);
            $table->unsignedInteger('returned_quantity')->default(0);
            $table->timestamps();

            $table->unique(['sale_id', 'batch_id']);
        });

        $legacySales = DB::table('sales')->orderBy('id')->get();
        foreach ($legacySales as $sale) {
            $product = DB::table('products')->where('id', $sale->product_id)->first();
            if (!$product) {
                continue;
            }

            $purchaseId = $product->purchase_id;
            $batch = DB::table('batches')->where('purchase_id', $purchaseId)->orderBy('expiry_date')->first();
            if (!$batch) {
                continue;
            }

            DB::table('sale_batch_allocations')->insert([
                'sale_id' => $sale->id,
                'batch_id' => $batch->id,
                'quantity' => (int) ($sale->quantity ?? 0),
                'returned_quantity' => (int) ($sale->returned_quantity ?? 0),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_batch_allocations');
    }
};

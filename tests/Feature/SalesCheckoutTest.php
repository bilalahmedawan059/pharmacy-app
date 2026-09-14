<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Purchase;
use App\Models\SaleTransaction;
use App\Models\Sales;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalesCheckoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_multiple_medicines_are_saved_as_one_invoice_and_stock_is_decreased(): void
    {
        $user = User::factory()->create();
        $firstPurchase = Purchase::create(['name' => 'Test Medicine One', 'price' => 5, 'quantity' => 10, 'expiry_date' => '2030-01-01']);
        $secondPurchase = Purchase::create(['name' => 'Test Medicine Two', 'price' => 3, 'quantity' => 8, 'expiry_date' => '2030-01-01']);
        $first = Product::create(['purchase_id' => $firstPurchase->id, 'price' => 5, 'discount' => 0, 'product_code' => '1111111111']);
        $second = Product::create(['purchase_id' => $secondPurchase->id, 'price' => 3, 'discount' => 0, 'product_code' => '2222222222']);

        $response = $this->actingAs($user)->post(route('sales'), [
            'items' => [
                ['product_id' => $first->id, 'quantity' => 2],
                ['product_id' => $second->id, 'quantity' => 1],
            ],
            'amount_received' => 20,
        ]);

        $transaction = SaleTransaction::first();
        $response->assertRedirect(route('sales.transaction.print', $transaction));
        $this->assertSame(2, $transaction->lines()->count());
        $this->assertSame('13.00', $transaction->total);
        $this->assertSame('7.00', $transaction->change_amount);
        $this->assertSame('8', (string) $firstPurchase->fresh()->quantity);
        $this->assertSame('7', (string) $secondPurchase->fresh()->quantity);
    }

    public function test_insufficient_stock_rolls_back_the_whole_checkout(): void
    {
        $user = User::factory()->create();
        $purchase = Purchase::create(['name' => 'Test Medicine', 'price' => 5, 'quantity' => 1, 'expiry_date' => '2030-01-01']);
        $product = Product::create(['purchase_id' => $purchase->id, 'price' => 5, 'discount' => 0, 'product_code' => '3333333333']);

        $response = $this->actingAs($user)->from(route('sales'))->post(route('sales'), [
            'items' => [['product_id' => $product->id, 'quantity' => 2]],
            'amount_received' => 10,
        ]);

        $response->assertRedirect(route('sales'));
        $this->assertSame(0, SaleTransaction::count());
        $this->assertSame('1', (string) $purchase->fresh()->quantity);
    }
}
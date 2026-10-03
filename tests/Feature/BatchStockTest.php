<?php

namespace Tests\Feature;

use App\Models\Batch;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\SaleTransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class BatchStockTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_purchase_creates_a_legacy_batch_and_keeps_total_quantity_in_sync(): void
    {
        $purchase = Purchase::create([
            'name' => 'Batch Test Medicine',
            'price' => 10,
            'quantity' => 12,
            'expiry_date' => '2030-03-31',
        ]);

        $this->assertDatabaseHas('batches', [
            'purchase_id' => $purchase->id,
            'batch_number' => 'LEGACY-' . $purchase->id,
            'quantity_available' => 12,
        ]);
        $this->assertSame('12', (string) $purchase->fresh()->quantity);
    }

    public function test_checkout_uses_the_earliest_expiring_batch_first(): void
    {
        $user = User::factory()->create();
        $this->grantCreateSales($user, 'super-admin');

        $purchase = Purchase::create([
            'name' => 'FEFO Medicine',
            'price' => 5,
            'quantity' => 0,
            'expiry_date' => '2030-12-31',
        ]);

        $oldBatch = Batch::create([
            'purchase_id' => $purchase->id,
            'branch_id' => null,
            'batch_number' => 'B-2',
            'expiry_date' => '2030-02-28',
            'quantity_received' => 8,
            'quantity_available' => 8,
        ]);

        $newBatch = Batch::create([
            'purchase_id' => $purchase->id,
            'branch_id' => null,
            'batch_number' => 'B-1',
            'expiry_date' => '2030-10-31',
            'quantity_received' => 10,
            'quantity_available' => 10,
        ]);

        $purchase->refresh()->update(['quantity' => $oldBatch->quantity_available + $newBatch->quantity_available]);
        $product = Product::create(['purchase_id' => $purchase->id, 'price' => 5, 'discount' => 0, 'product_code' => '9876543210']);

        $response = $this->actingAs($user)->from(route('sales'))->post(route('sales'), [
            'items' => [['product_id' => $product->id, 'quantity' => 6]],
            'amount_received' => 30,
        ]);

        $response->assertRedirect(route('sales.transaction.print', SaleTransaction::first()));
        $this->assertSame(2, $oldBatch->fresh()->quantity_available);
        $this->assertSame(10, $newBatch->fresh()->quantity_available);
    }

    private function grantCreateSales(User $user, string $roleName = 'sales-person'): void
    {
        $permissions = collect(['create-sales', 'view-sales'])->map(function ($name) {
            return Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        });

        $role = \App\Models\Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web', 'pharmacy_id' => null]);
        $role->givePermissionTo($permissions);
        $user->assignRole($role);
    }
}

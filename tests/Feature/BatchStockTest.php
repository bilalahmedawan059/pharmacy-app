<?php

namespace Tests\Feature;

use App\Models\Batch;
use App\Models\Branch;
use App\Models\Category;
use App\Models\Pharmacy;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\SaleBatchAllocation;
use App\Models\SaleTransaction;
use App\Models\Sales;
use App\Models\Supplier;
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

    public function test_checkout_splits_one_sale_line_across_batches_in_fefo_order(): void
    {
        [$user, $purchase, $product] = $this->saleFixture(0);
        $first = $this->batch($purchase, 'SPLIT-1', '2030-02-28', 3);
        $second = $this->batch($purchase, 'SPLIT-2', '2030-05-31', 5);

        $this->checkout($user, [[$product, 6]]);

        $line = Sales::firstOrFail();
        $allocations = $line->allocations()->orderBy('batch_id')->get();
        $this->assertCount(2, $allocations);
        $this->assertSame([$first->id, $second->id], $allocations->pluck('batch_id')->all());
        $this->assertSame([3, 3], $allocations->pluck('quantity')->all());
        $this->assertSame(0, $first->fresh()->quantity_available);
        $this->assertSame(2, $second->fresh()->quantity_available);
    }

    public function test_expired_batch_is_not_sold_even_when_it_is_the_only_stock(): void
    {
        [$user, $purchase, $product] = $this->saleFixture(0);
        $expired = $this->batch($purchase, 'OLD', '2020-01-31', 4);

        $response = $this->actingAs($user)->from(route('sales'))->post(route('sales'), [
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
            'amount_received' => 5,
        ]);

        $response->assertRedirect(route('sales'))->assertSessionHasErrors('items');
        $this->assertSame(0, SaleTransaction::count());
        $this->assertSame(4, $expired->fresh()->quantity_available);
    }

    public function test_insufficient_stock_rolls_back_all_lines_in_a_checkout(): void
    {
        [$user, $firstPurchase, $firstProduct] = $this->saleFixture(0, 'First medicine');
        $firstBatch = $this->batch($firstPurchase, 'ENOUGH', '2030-02-28', 5);
        [, $secondPurchase, $secondProduct] = $this->saleFixture(0, 'Second medicine', $user);
        $secondBatch = $this->batch($secondPurchase, 'SHORT', '2030-03-31', 1);

        $response = $this->actingAs($user)->from(route('sales'))->post(route('sales'), [
            'items' => [
                ['product_id' => $firstProduct->id, 'quantity' => 2],
                ['product_id' => $secondProduct->id, 'quantity' => 2],
            ],
            'amount_received' => 20,
        ]);

        $response->assertRedirect(route('sales'))->assertSessionHasErrors('items');
        $this->assertSame(0, SaleTransaction::count());
        $this->assertSame(5, $firstBatch->fresh()->quantity_available);
        $this->assertSame(1, $secondBatch->fresh()->quantity_available);
    }

    public function test_return_from_a_split_sale_restores_the_original_batches(): void
    {
        [$user, $purchase, $product] = $this->saleFixture(0);
        $first = $this->batch($purchase, 'RETURN-1', '2030-02-28', 5);
        $second = $this->batch($purchase, 'RETURN-2', '2030-03-31', 7);
        $this->checkout($user, [[$product, 9]]);

        $transaction = SaleTransaction::firstOrFail();
        $line = $transaction->lines()->firstOrFail();
        $this->actingAs($user)->post(route('sales.transaction.return', $transaction), [
            'returns' => [$line->id => 9],
        ])->assertSessionHasNoErrors();

        $this->assertSame(5, $first->fresh()->quantity_available);
        $this->assertSame(7, $second->fresh()->quantity_available);
        $this->assertSame([5, 4], $line->allocations()->orderBy('batch_id')->pluck('returned_quantity')->all());
    }

    public function test_purchase_intake_tops_up_matching_branch_batch_and_rejects_a_different_expiry(): void
    {
        $user = User::factory()->create();
        $this->grantPermissions($user, ['create-purchase', 'view-purchase'], 'super-admin');
        $category = Category::create(['name' => 'Batch category']);
        $supplier = Supplier::create([
            'name' => 'Batch supplier',
            'email' => 'batch-supplier@example.test',
            'company' => 'Test company',
            'address' => 'Test address',
            'product' => 'Medicine',
        ]);
        $input = [
            'name' => 'Top-up medicine',
            'category' => $category->id,
            'supplier' => $supplier->id,
            'price' => 10,
            'quantity' => 3,
            'batch_number' => 'TOP-UP-1',
            'expiry_date' => '03/2030',
        ];

        $this->actingAs($user)->post(route('store-stock'), $input)->assertRedirect(route('purchases'));
        $this->actingAs($user)->post(route('store-stock'), array_merge($input, ['quantity' => 4]))->assertRedirect(route('purchases'));
        $purchase = Purchase::where('name', 'Top-up medicine')->firstOrFail();
        $batch = $purchase->batches()->where('batch_number', 'TOP-UP-1')->firstOrFail();
        $this->assertSame(7, $batch->quantity_received);
        $this->assertSame(7, $batch->quantity_available);

        $response = $this->actingAs($user)->from(route('add-purchase'))->post(route('store-stock'), array_merge($input, [
            'expiry_date' => '04/2030',
        ]));
        $response->assertRedirect(route('add-purchase'))->assertSessionHasErrors('batch_number');
        $this->assertSame(1, $purchase->batches()->where('batch_number', 'TOP-UP-1')->count());
    }

    public function test_purchase_intake_uses_branch_for_batch_identity_and_top_up(): void
    {
        $pharmacy = $this->pharmacy();
        $firstBranch = $this->branch($pharmacy, 'Intake A');
        $secondBranch = $this->branch($pharmacy, 'Intake B');
        $user = User::factory()->create(['pharmacy_id' => $pharmacy->id, 'branch_id' => $firstBranch->id]);
        $this->grantPermissions($user, ['create-purchase', 'view-purchase'], 'branch-manager');
        $category = Category::create(['name' => 'Branch intake category', 'pharmacy_id' => $pharmacy->id]);
        $supplier = Supplier::create([
            'name' => 'Branch intake supplier',
            'email' => 'branch-intake@example.test',
            'company' => 'Test company',
            'address' => 'Test address',
            'product' => 'Medicine',
            'pharmacy_id' => $pharmacy->id,
        ]);
        $input = [
            'name' => 'Branch intake medicine',
            'category' => $category->id,
            'supplier' => $supplier->id,
            'price' => 10,
            'quantity' => 2,
            'batch_number' => 'SHARED-NUMBER',
            'expiry_date' => '03/2030',
            'branch_id' => $firstBranch->id,
        ];

        $this->actingAs($user)->post(route('store-stock'), $input)->assertRedirect(route('purchases'));
        $this->actingAs($user)->post(route('store-stock'), array_merge($input, ['quantity' => 3]))->assertRedirect(route('purchases'));
        $this->actingAs($user)->post(route('store-stock'), array_merge($input, ['branch_id' => $secondBranch->id]))->assertRedirect(route('purchases'));

        $purchase = Purchase::where('name', 'Branch intake medicine')->firstOrFail();
        $this->assertSame(2, $purchase->batches()->where('batch_number', 'SHARED-NUMBER')->count());
        $firstBatch = $purchase->batches()->where('branch_id', $firstBranch->id)->firstOrFail();
        $secondBatch = $purchase->batches()->where('branch_id', $secondBranch->id)->firstOrFail();
        $this->assertSame(5, $firstBatch->quantity_available);
        $this->assertSame(2, $secondBatch->quantity_available);
        $this->assertSame($pharmacy->id, $firstBatch->pharmacy_id);
    }

    public function test_unallocated_batch_can_be_edited_without_overwriting_cached_totals(): void
    {
        $user = User::factory()->create();
        $this->grantPermissions($user, ['update-purchase', 'view-purchase'], 'super-admin');
        $category = Category::create(['name' => 'Editable category']);
        $supplier = Supplier::create([
            'name' => 'Editable supplier',
            'email' => 'editable-supplier@example.test',
            'company' => 'Test company',
            'address' => 'Test address',
            'product' => 'Medicine',
        ]);
        $purchase = Purchase::create([
            'name' => 'Editable medicine',
            'category_id' => $category->id,
            'supplier_id' => $supplier->id,
            'price' => 10,
            'quantity' => 0,
            'expiry_date' => '2030-01-31',
        ]);
        $batch = $this->batch($purchase, 'BEFORE', '2030-02-28', 4);

        $this->actingAs($user)->put(route('edit-purchase', $purchase), [
            'name' => 'Renamed medicine',
            'category' => $category->id,
            'supplier' => $supplier->id,
            'price' => 12,
            'batches' => [
                $batch->id => [
                    'batch_number' => 'AFTER',
                    'expiry_date' => '2031-04-30',
                    'quantity_received' => 6,
                ],
            ],
        ])->assertRedirect(route('purchases'));

        $this->assertSame('AFTER', $batch->fresh()->batch_number);
        $this->assertSame(6, $batch->fresh()->quantity_available);
        $this->assertSame('6', (string) $purchase->fresh()->quantity);
        $this->assertSame('2031-04-30', $purchase->fresh()->expiry_date->toDateString());
        $this->assertSame('Renamed medicine', $purchase->fresh()->name);
    }

    public function test_checkout_does_not_use_stock_from_another_branch(): void
    {
        $pharmacy = $this->pharmacy();
        $targetBranch = $this->branch($pharmacy, 'Target');
        $otherBranch = $this->branch($pharmacy, 'Other');
        $user = User::factory()->create(['pharmacy_id' => $pharmacy->id, 'branch_id' => $targetBranch->id]);
        $this->grantPermissions($user, ['create-sales', 'view-sales'], 'super-admin');
        $purchase = Purchase::create([
            'name' => 'Branch-separated stock',
            'price' => 5,
            'quantity' => 0,
            'expiry_date' => '2030-01-31',
            'pharmacy_id' => $pharmacy->id,
        ]);
        $product = Product::create([
            'purchase_id' => $purchase->id,
            'price' => 5,
            'discount' => 0,
            'product_code' => '1234509876',
            'pharmacy_id' => $pharmacy->id,
        ]);
        $otherBatch = Batch::create([
            'purchase_id' => $purchase->id,
            'branch_id' => $otherBranch->id,
            'batch_number' => 'OTHER-BRANCH',
            'expiry_date' => '2030-01-31',
            'quantity_received' => 5,
            'quantity_available' => 5,
            'pharmacy_id' => $pharmacy->id,
        ]);
        $this->actingAs($user)->post(route('getProductByBarcode'), [
            'barcode' => $product->product_code,
            'branch_id' => $targetBranch->id,
        ])->assertOk()
            ->assertJsonPath('product.stock', 0)
            ->assertJsonPath('product.batches', []);

        $response = $this->actingAs($user)->from(route('sales', ['branch_id' => $targetBranch->id]))->post(route('sales'), [
            'branch_id' => $targetBranch->id,
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
            'amount_received' => 5,
        ]);

        $response->assertRedirect(route('sales', ['branch_id' => $targetBranch->id]))->assertSessionHasErrors('items');
        $this->assertSame(5, $otherBatch->fresh()->quantity_available);
        $this->assertSame(0, SaleTransaction::count());
    }

    public function test_batch_with_sale_allocations_cannot_be_edited(): void
    {
        [$user, $purchase, $product, $batch, $category, $supplier] = $this->saleWithAllocatedBatch(
            ['update-purchase', 'view-purchase', 'create-sales', 'view-sales']
        );

        $response = $this->actingAs($user)->from(route('edit-purchase', $purchase))->put(route('edit-purchase', $purchase), [
            'name' => $purchase->name,
            'category' => $category->id,
            'supplier' => $supplier->id,
            'price' => 10,
            'batches' => [
                $batch->id => [
                    'batch_number' => 'CHANGED',
                    'expiry_date' => '2030-02-28',
                    'quantity_received' => 8,
                ],
            ],
        ]);

        $response->assertRedirect(route('edit-purchase', $purchase))->assertSessionHasErrors('purchase');
        $this->assertSame('ALLOCATED', $batch->fresh()->batch_number);
    }

    public function test_purchase_with_sale_allocations_cannot_be_deleted(): void
    {
        [$user, $purchase, , $batch] = $this->saleWithAllocatedBatch(['destroy-purchase', 'view-purchase', 'create-sales', 'view-sales']);

        $response = $this->actingAs($user)->from(route('purchases'))->delete(route('delete-stock'), ['id' => $purchase->id]);

        $response->assertRedirect(route('purchases'))->assertSessionHasErrors('purchase');
        $this->assertDatabaseHas('purchases', ['id' => $purchase->id, 'deleted_at' => null]);
        $this->assertDatabaseHas('batches', ['id' => $batch->id, 'deleted_at' => null]);
    }

    private function saleFixture(int $quantity, string $name = 'Batch test medicine', ?User $user = null): array
    {
        $user = $user ?: User::factory()->create();
        $this->grantPermissions($user, ['create-sales', 'view-sales', 'update-sales'], 'super-admin');
        $purchase = Purchase::create([
            'name' => $name,
            'price' => 5,
            'quantity' => $quantity,
            'expiry_date' => '2030-12-31',
        ]);
        $product = Product::create([
            'purchase_id' => $purchase->id,
            'price' => 5,
            'discount' => 0,
            'product_code' => (string) random_int(1000000000, 9999999999),
        ]);
        return [$user, $purchase, $product];
    }

    private function saleWithAllocatedBatch(array $permissions): array
    {
        $user = User::factory()->create();
        $this->grantPermissions($user, $permissions, 'super-admin');
        $category = Category::create(['name' => 'Allocation category']);
        $supplier = Supplier::create([
            'name' => 'Allocation supplier',
            'email' => 'allocation-supplier@example.test',
            'company' => 'Test company',
            'address' => 'Test address',
            'product' => 'Medicine',
        ]);
        $purchase = Purchase::create([
            'name' => 'Allocated batch medicine',
            'category_id' => $category->id,
            'supplier_id' => $supplier->id,
            'price' => 10,
            'quantity' => 0,
            'expiry_date' => '2030-12-31',
        ]);
        $batch = $this->batch($purchase, 'ALLOCATED', '2030-12-31', 10);
        $product = Product::create([
            'purchase_id' => $purchase->id,
            'price' => 10,
            'discount' => 0,
            'product_code' => '9988776655',
        ]);
        $this->checkout($user, [[$product, 2]]);
        return [$user, $purchase, $product, $batch, $category, $supplier];
    }

    private function checkout(User $user, array $items): void
    {
        $this->actingAs($user)->post(route('sales'), [
            'items' => array_map(function ($item) {
                return ['product_id' => $item[0]->id, 'quantity' => $item[1]];
            }, $items),
            'amount_received' => 1000,
        ])->assertRedirect();
    }

    private function batch(Purchase $purchase, string $number, string $expiry, int $quantity, ?int $branchId = null): Batch
    {
        return Batch::create([
            'purchase_id' => $purchase->id,
            'branch_id' => $branchId,
            'batch_number' => $number,
            'expiry_date' => $expiry,
            'quantity_received' => $quantity,
            'quantity_available' => $quantity,
        ]);
    }

    private function grantPermissions(User $user, array $names, string $roleName): void
    {
        $permissions = collect($names)->map(function ($name) {
            return Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        });
        $role = \App\Models\Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web', 'pharmacy_id' => null]);
        $role->givePermissionTo($permissions);
        $user->assignRole($role);
    }

    private function pharmacy(): Pharmacy
    {
        return Pharmacy::create([
            'business_name' => 'Batch Pharmacy',
            'owner_name' => 'Batch Owner',
            'owner_email' => 'batch-owner@example.test',
            'address' => 'Test address',
            'is_manufacturing' => false,
            'max_employees' => 10,
            'status' => 'active',
        ]);
    }

    private function branch(Pharmacy $pharmacy, string $name): Branch
    {
        return Branch::create([
            'pharmacy_id' => $pharmacy->id,
            'name' => $name,
            'address' => 'Test address',
            'city' => 'Test city',
            'contact' => '123456789',
            'license_number' => strtoupper(str_replace(' ', '-', $name)),
            'status' => 'active',
        ]);
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

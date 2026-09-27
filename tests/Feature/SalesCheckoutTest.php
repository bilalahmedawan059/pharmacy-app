<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Purchase;
use App\Models\SaleTransaction;
use App\Models\Sales;
use App\Models\Branch;
use App\Models\Pharmacy;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class SalesCheckoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_multiple_medicines_are_saved_as_one_invoice_and_stock_is_decreased(): void
    {
        $user = User::factory()->create();
        $this->grantCreateSales($user, 'super-admin');
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

    public function test_checkout_saves_the_selected_branch_on_the_invoice(): void
    {
        $pharmacy = Pharmacy::create([
            'business_name' => 'Test Pharmacy',
            'owner_name' => 'Test Owner',
            'owner_email' => 'owner@example.test',
            'address' => 'Test address',
            'is_manufacturing' => false,
            'max_employees' => 10,
            'status' => 'active',
        ]);
        $branch = Branch::create([
            'pharmacy_id' => $pharmacy->id,
            'name' => 'North Branch',
            'address' => 'Test address',
            'city' => 'Test city',
            'contact' => '123456789',
            'license_number' => 'TEST-BRANCH-1',
            'status' => 'active',
        ]);
        $user = User::factory()->create([
            'pharmacy_id' => $pharmacy->id,
            'branch_id' => $branch->id,
        ]);
        $this->grantCreateSales($user, 'branch-manager');
        $purchase = Purchase::create(['name' => 'Branch Medicine', 'price' => 5, 'quantity' => 3, 'expiry_date' => '2030-01-01', 'pharmacy_id' => $pharmacy->id]);
        $product = Product::create(['purchase_id' => $purchase->id, 'price' => 5, 'discount' => 0, 'product_code' => '4444444444', 'pharmacy_id' => $pharmacy->id]);

        $response = $this->actingAs($user)->post(route('sales'), [
            'branch_id' => $branch->id,
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
            'amount_received' => 5,
        ]);

        $transaction = SaleTransaction::first();
        $response->assertRedirect(route('sales.transaction.print', ['transaction' => $transaction, 'branch_id' => $branch->id]));
        $this->assertSame($branch->id, $transaction->branch_id);

        $otherBranch = Branch::create([
            'pharmacy_id' => $pharmacy->id,
            'name' => 'South Branch',
            'address' => 'Other address',
            'city' => 'Test city',
            'contact' => '987654321',
            'license_number' => 'TEST-BRANCH-2',
            'status' => 'active',
        ]);
        SaleTransaction::create([
            'invoice_number' => 'INV-OTHER-BRANCH',
            'user_id' => $user->id,
            'branch_id' => $otherBranch->id,
            'subtotal' => 5,
            'discount' => 0,
            'total' => 5,
            'amount_received' => 5,
            'change_amount' => 0,
            'payment_method' => 'cash',
            'payment_status' => 'paid',
        ]);

        $this->actingAs($user)->get(route('sales', ['branch_id' => $branch->id]))
            ->assertOk()
            ->assertSee($transaction->invoice_number)
            ->assertDontSee('INV-OTHER-BRANCH')
            ->assertSee('id="sales-branch" name="branch_id" class="select2 form-control"', false)
            ->assertDontSee('product-dropdown');

        for ($invoiceIndex = 1; $invoiceIndex <= 10; $invoiceIndex++) {
            SaleTransaction::create([
                'invoice_number' => 'INV-PAGE-' . $invoiceIndex,
                'user_id' => $user->id,
                'branch_id' => $branch->id,
                'subtotal' => 5,
                'discount' => 0,
                'total' => 5,
                'amount_received' => 5,
                'change_amount' => 0,
                'payment_method' => 'cash',
                'payment_status' => 'paid',
                'created_at' => now()->addSeconds($invoiceIndex),
                'updated_at' => now()->addSeconds($invoiceIndex),
            ]);
        }

        $this->actingAs($user)->get(route('sales', ['branch_id' => $branch->id, 'page' => 2]))
            ->assertOk()
            ->assertSee($transaction->invoice_number)
            ->assertDontSee('INV-OTHER-BRANCH');
    }

    public function test_insufficient_stock_rolls_back_the_whole_checkout(): void
    {
        $user = User::factory()->create();
        $this->grantCreateSales($user, 'super-admin');
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

    private function grantCreateSales(User $user, string $roleName = 'sales-person'): void
    {
        $permissions = collect(['create-sales', 'view-sales'])->map(function ($name) {
            return Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        });
        $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web', 'pharmacy_id' => null]);
        $role->givePermissionTo($permissions);
        $user->assignRole($role);
    }
}
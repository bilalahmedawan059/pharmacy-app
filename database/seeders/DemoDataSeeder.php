<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Category;
use App\Models\Pharmacy;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\SaleTransaction;
use App\Models\Sales;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoDataSeeder extends Seeder
{
    public function run()
    {
        $pharmacies = [
            [
                'key' => 'demo',
                'tax_type' => 'NTN',
                'tax_id' => '1234567',
                'business_name' => 'Demo Pharmacy',
                'owner_name' => 'Demo Pharmacy Owner',
                'owner_email' => 'owner@demo-pharmacy.test',
                'city' => 'Islamabad',
                'phone_number' => '03000000000',
            ],
            [
                'key' => 'city',
                'tax_type' => 'NTN',
                'tax_id' => '7654321',
                'business_name' => 'City Care Pharmacy',
                'owner_name' => 'City Care Owner',
                'owner_email' => 'owner@city-care.test',
                'city' => 'Rawalpindi',
                'phone_number' => '03111111111',
            ],
        ];

        foreach ($pharmacies as $pharmacyData) {
            $pharmacy = Pharmacy::withoutGlobalScopes()->updateOrCreate(
                ['tax_type' => $pharmacyData['tax_type'], 'tax_id' => $pharmacyData['tax_id']],
                [
                    'business_name' => $pharmacyData['business_name'],
                    'owner_name' => $pharmacyData['owner_name'],
                    'owner_email' => $pharmacyData['owner_email'],
                    'city' => $pharmacyData['city'],
                    'phone_number' => $pharmacyData['phone_number'],
                    'address' => $pharmacyData['city'] . ' Main Market',
                    'contact_address' => $pharmacyData['city'] . ' Main Market',
                    'status' => 'active',
                    'is_manufacturing' => false,
                    'max_employees' => 50,
                    'launched_at' => now(),
                ]
            );

            $branches = [
                $this->branch($pharmacy, 'Main Branch', $pharmacyData['city'] . ' Main Market', '03000000001', $pharmacyData['key'] . '-MAIN'),
                $this->branch($pharmacy, 'Express Branch', $pharmacyData['city'] . ' Express Road', '03000000002', $pharmacyData['key'] . '-EXPRESS'),
            ];

            $this->seedUsers($pharmacy, $branches);
            $this->seedInventoryAndSales($pharmacy, $branches);
        }
    }

    private function branch(Pharmacy $pharmacy, $name, $address, $contact, $license)
    {
        return Branch::updateOrCreate(
            ['license_number' => 'DEMO-' . strtoupper($license)],
            [
                'pharmacy_id' => $pharmacy->id,
                'name' => $name,
                'address' => $address,
                'city' => $pharmacy->city,
                'contact' => $contact,
                'status' => 'active',
            ]
        );
    }

    private function seedUsers(Pharmacy $pharmacy, array $branches)
    {
        $users = [
            ['role' => 'admin', 'name' => $pharmacy->business_name . ' Admin', 'email' => 'admin+' . $pharmacy->id . '@demo-pharmacy.test', 'branch' => 0],
            ['role' => 'branch-manager', 'name' => $pharmacy->business_name . ' Manager', 'email' => 'manager+' . $pharmacy->id . '@demo-pharmacy.test', 'branch' => 0],
            ['role' => 'pharmacist', 'name' => $pharmacy->business_name . ' Pharmacist', 'email' => 'pharmacist+' . $pharmacy->id . '@demo-pharmacy.test', 'branch' => 0],
            ['role' => 'salesman', 'name' => $pharmacy->business_name . ' Salesman', 'email' => 'salesman+' . $pharmacy->id . '@demo-pharmacy.test', 'branch' => 1],
            ['role' => 'cashier', 'name' => $pharmacy->business_name . ' Cashier', 'email' => 'cashier+' . $pharmacy->id . '@demo-pharmacy.test', 'branch' => 1],
        ];

        foreach ($users as $userData) {
            $user = User::updateOrCreate(
                ['email' => $userData['email']],
                [
                    'pharmacy_id' => $pharmacy->id,
                    'branch_id' => $branches[$userData['branch']]->id,
                    'name' => $userData['name'],
                    'phone' => $branches[$userData['branch']]->contact,
                    'password' => Hash::make('password'),
                    'is_invited' => false,
                ]
            );
            $user->syncRoles([$userData['role']]);
        }
    }

    private function seedInventoryAndSales(Pharmacy $pharmacy, array $branches)
    {
        $catalog = [
            ['category' => 'Pain Relief', 'medicine' => 'Paracetamol 500mg', 'price' => 120, 'quantity' => 100, 'expiry' => now()->addMonths(18), 'code' => 'PCM-' . $pharmacy->id],
            ['category' => 'Antibiotics', 'medicine' => 'Amoxicillin 500mg', 'price' => 350, 'quantity' => 70, 'expiry' => now()->addMonths(15), 'code' => 'AMX-' . $pharmacy->id],
            ['category' => 'Diabetes Care', 'medicine' => 'Metformin 500mg', 'price' => 280, 'quantity' => 85, 'expiry' => now()->addMonths(20), 'code' => 'MET-' . $pharmacy->id],
            ['category' => 'Vitamins', 'medicine' => 'Multivitamin Tablets', 'price' => 650, 'quantity' => 55, 'expiry' => now()->addMonths(24), 'code' => 'VIT-' . $pharmacy->id],
        ];

        $products = [];
        foreach ($catalog as $item) {
            $category = Category::withoutGlobalScopes()->firstOrCreate(
                ['name' => $item['category'] . ' - Pharmacy ' . $pharmacy->id],
                ['pharmacy_id' => $pharmacy->id]
            );
            $supplier = Supplier::withoutGlobalScopes()->firstOrCreate(
                ['email' => 'supplier' . $pharmacy->id . '@demo.test'],
                [
                    'name' => 'Health Supplier ' . $pharmacy->id,
                    'company' => 'Health Distribution ' . $pharmacy->id,
                    'phone' => '03222222222',
                    'address' => $pharmacy->city . ' Industrial Area',
                    'product' => 'Medicines and healthcare products',
                    'description' => 'Seeded demo supplier',
                    'pharmacy_id' => $pharmacy->id,
                ]
            );
            $purchase = Purchase::withoutGlobalScopes()->firstOrCreate(
                ['name' => $item['medicine'] . ' - Pharmacy ' . $pharmacy->id],
                [
                    'category_id' => $category->id,
                    'supplier_id' => $supplier->id,
                    'price' => $item['price'],
                    'quantity' => $item['quantity'],
                    'expiry_date' => $item['expiry']->toDateString(),
                    'pharmacy_id' => $pharmacy->id,
                ]
            );
            $products[] = Product::withoutGlobalScopes()->updateOrCreate(
                ['product_code' => $item['code']],
                [
                    'purchase_id' => $purchase->id,
                    'price' => $item['price'],
                    'discount' => 0,
                    'description' => $item['medicine'] . ' demo stock',
                    'pharmacy_id' => $pharmacy->id,
                ]
            );
        }

        $cashier = User::where('pharmacy_id', $pharmacy->id)->whereHas('roles', function ($query) {
            $query->where('name', 'cashier');
        })->first();
        if (!$cashier) {
            return;
        }

        $invoice = SaleTransaction::withoutGlobalScopes()->updateOrCreate(
            ['invoice_number' => 'DEMO-INV-' . $pharmacy->id],
            [
                'user_id' => $cashier->id,
                'customer_name' => 'Demo Customer',
                'subtotal' => 820,
                'discount' => 20,
                'total' => 800,
                'amount_received' => 1000,
                'change_amount' => 200,
                'payment_method' => 'cash',
                'payment_status' => 'paid',
                'pharmacy_id' => $pharmacy->id,
            ]
        );

        Sales::withoutGlobalScopes()->updateOrCreate(
            ['sale_transaction_id' => $invoice->id, 'product_id' => $products[0]->id],
            [
                'quantity' => 2,
                'total_price' => 240,
                'pharmacy_id' => $pharmacy->id,
            ]
        );
        Sales::withoutGlobalScopes()->updateOrCreate(
            ['sale_transaction_id' => $invoice->id, 'product_id' => $products[1]->id],
            [
                'quantity' => 1,
                'total_price' => 350,
                'pharmacy_id' => $pharmacy->id,
            ]
        );
    }
}

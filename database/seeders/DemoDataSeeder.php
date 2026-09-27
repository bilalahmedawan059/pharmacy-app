<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Pharmacy;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\PermissionRegistrar;

class DemoDataSeeder extends Seeder
{
    /**
     * Seed demo/test data.
     *
     * This seeder will now CREATE (or reuse, if they already exist):
     * - Pharmacy: "Bilal Pharmacy"
     * - Branch:   "Main Branch"
     * - Admin user
     * - Branch Manager user
     *
     * It then seeds categories, suppliers, medicaments, purchases,
     * products and stock into that pharmacy/branch, exactly as before.
     */
    public function run(): void
    {
        $pharmacy = $this->seedPharmacy();
        $branch   = $this->seedBranch($pharmacy);

        $this->seedUsers($pharmacy, $branch);

        $this->seedInventory($pharmacy);
    }

    /**
     * Create (or reuse) the pharmacy.
     */
    private function seedPharmacy(): Pharmacy
    {
        return Pharmacy::withoutGlobalScopes()->firstOrCreate(
            [
                'business_name' => 'Bilal Pharmacy',
            ],
            [
                'owner_name'        => 'Bilal Ahmed',
                'owner_email'       => 'bilal@gmail.com',
                'tax_type'          => 'NTN',
                'tax_id'            => '1234567',
                'contact_address'   => 'pind paracha PO jhangi syden islamabad',
                'city'              => 'Islamabad',
                'status'            => 'active',
                'launched_at'       => now(),
                'address'           => 'pind paracha PO jhangi syden islamabad',
                'revenue'           => 0.00,
                'phone_number'      => '03495101379',
                'is_manufacturing'  => 0,
                'max_employees'     => 10,
            ]
        );
    }

    /**
     * Create (or reuse) the main branch for the pharmacy.
     */
    private function seedBranch(Pharmacy $pharmacy): object
    {
        $branch = DB::table('branches')
            ->where('pharmacy_id', $pharmacy->id)
            ->where('name', 'Main Branch')
            ->first();

        if ($branch) {
            return $branch;
        }

        $branchId = DB::table('branches')->insertGetId([
            'pharmacy_id'    => $pharmacy->id,
            'name'           => 'Main Branch',
            'address'        => $pharmacy->address,
            'city'           => $pharmacy->city,
            'contact'        => $pharmacy->phone_number,
            'license_number' => '12345678',
            'status'         => 'active',
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);

        return DB::table('branches')->where('id', $branchId)->first();
    }

    /**
     * Create (or reuse) an admin user and a branch-manager user,
     * both attached to the pharmacy/branch, with their roles assigned.
     */
    private function seedUsers(Pharmacy $pharmacy, object $branch): void
    {
        // Roles in this app are scoped per-pharmacy (spatie/laravel-permission
        // "teams" feature — the `roles` table has a `pharmacy_id` column),
        // so the permission team id must be set before assigning a role.
        app(PermissionRegistrar::class)->setPermissionsTeamId($pharmacy->id);

        $admin = User::withoutGlobalScopes()->firstOrCreate(
            ['email' => 'admin.bilal@demo.test'],
            [
                'pharmacy_id' => $pharmacy->id,
                'branch_id'   => $branch->id,
                'name'        => 'Bilal Admin',
                'phone'       => '03495101379',
                'password'    => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        if (! $admin->hasRole('admin')) {
            $admin->assignRole('admin');
        }

        $manager = User::withoutGlobalScopes()->firstOrCreate(
            ['email' => 'manager.bilal@demo.test'],
            [
                'pharmacy_id' => $pharmacy->id,
                'branch_id'   => $branch->id,
                'name'        => 'Bilal Branch Manager',
                'phone'       => '03495101380',
                'password'    => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        if (! $manager->hasRole('branch-manager')) {
            $manager->assignRole('branch-manager');
        }
    }

    /**
     * Add categories, suppliers, medicaments, purchases,
     * products and stock to the pharmacy.
     */
    private function seedInventory(Pharmacy $pharmacy): void
    {
        DB::transaction(function () use ($pharmacy) {

            /*
             * ---------------------------------------------------------
             * 1. Categories
             * ---------------------------------------------------------
             */
            $categories = [];

            foreach ([
                'Pain Relief',
                'Antibiotics',
                'Cold & Flu',
                'Vitamins',
                'Digestive Health',
            ] as $categoryName) {
                $category = Category::withoutGlobalScopes()->firstOrCreate(
                    [
                        'name' => $categoryName,
                        'pharmacy_id' => $pharmacy->id,
                    ]
                );

                $categories[$categoryName] = $category;
            }

            /*
             * ---------------------------------------------------------
             * 2. Suppliers
             * ---------------------------------------------------------
             */
            $supplier1 = Supplier::withoutGlobalScopes()->firstOrCreate(
                [
                    'email' => 'test-pharma-supplier@demo.test',
                ],
                [
                    'name' => 'Test Pharma Supplier',
                    'company' => 'Test Pharma Supplier',
                    'phone' => '03001234567',
                    'address' => $pharmacy->city . ' Industrial Area',
                    'product' => 'Medicines and healthcare products',
                    'description' => 'Seeded test supplier',
                    'pharmacy_id' => $pharmacy->id,
                ]
            );

            $supplier2 = Supplier::withoutGlobalScopes()->firstOrCreate(
                [
                    'email' => 'healthcare-distributor@demo.test',
                ],
                [
                    'name' => 'HealthCare Distributor',
                    'company' => 'HealthCare Distributor',
                    'phone' => '03111234567',
                    'address' => $pharmacy->city . ' Industrial Area',
                    'product' => 'Medicines and healthcare products',
                    'description' => 'Seeded test supplier',
                    'pharmacy_id' => $pharmacy->id,
                ]
            );

            /*
             * ---------------------------------------------------------
             * 3. Medicaments
             * ---------------------------------------------------------
             *
             * Medicaments are global in the supplied schema, so we
             * reuse an existing medicament if the barcode already exists.
             */
            $medicines = [
                [
                    'name' => 'Paracetamol 500mg',
                    'active_substance' => 'Paracetamol',
                    'barcode' => '890000000001',
                    'price' => 55,
                    'recipe_required' => 0,
                    'category' => 'Pain Relief',
                    'quantity' => 100,
                    'purchase_price' => 45,
                    'expiry' => '2028-06-30',
                    'supplier' => $supplier1,
                    'product_code' => 'TEST-PARA-500',
                    'discount' => 0,
                ],
                [
                    'name' => 'Brufen 400mg',
                    'active_substance' => 'Ibuprofen',
                    'barcode' => '890000000002',
                    'price' => 100,
                    'recipe_required' => 0,
                    'category' => 'Pain Relief',
                    'quantity' => 75,
                    'purchase_price' => 85,
                    'expiry' => '2027-12-31',
                    'supplier' => $supplier1,
                    'product_code' => 'TEST-BRUFEN-400',
                    'discount' => 5,
                ],
                [
                    'name' => 'Amoxicillin 500mg',
                    'active_substance' => 'Amoxicillin',
                    'barcode' => '890000000003',
                    'price' => 210,
                    'recipe_required' => 1,
                    'category' => 'Antibiotics',
                    'quantity' => 60,
                    'purchase_price' => 180,
                    'expiry' => '2027-08-31',
                    'supplier' => $supplier1,
                    'product_code' => 'TEST-AMOX-500',
                    'discount' => 0,
                ],
                [
                    'name' => 'Azithromycin 500mg',
                    'active_substance' => 'Azithromycin',
                    'barcode' => '890000000004',
                    'price' => 250,
                    'recipe_required' => 1,
                    'category' => 'Antibiotics',
                    'quantity' => 40,
                    'purchase_price' => 220,
                    'expiry' => '2027-05-31',
                    'supplier' => $supplier1,
                    'product_code' => 'TEST-AZITH-500',
                    'discount' => 10,
                ],
                [
                    'name' => 'Cetirizine 10mg',
                    'active_substance' => 'Cetirizine Hydrochloride',
                    'barcode' => '890000000005',
                    'price' => 85,
                    'recipe_required' => 0,
                    'category' => 'Cold & Flu',
                    'quantity' => 90,
                    'purchase_price' => 70,
                    'expiry' => '2028-01-31',
                    'supplier' => $supplier2,
                    'product_code' => 'TEST-CETIR-10',
                    'discount' => 0,
                ],
                [
                    'name' => 'Vitamin C 500mg',
                    'active_substance' => 'Ascorbic Acid',
                    'barcode' => '890000000006',
                    'price' => 180,
                    'recipe_required' => 0,
                    'category' => 'Vitamins',
                    'quantity' => 120,
                    'purchase_price' => 150,
                    'expiry' => '2028-09-30',
                    'supplier' => $supplier2,
                    'product_code' => 'TEST-VITC-500',
                    'discount' => 0,
                ],
                [
                    'name' => 'Omeprazole 20mg',
                    'active_substance' => 'Omeprazole',
                    'barcode' => '890000000007',
                    'price' => 135,
                    'recipe_required' => 0,
                    'category' => 'Digestive Health',
                    'quantity' => 80,
                    'purchase_price' => 110,
                    'expiry' => '2028-04-30',
                    'supplier' => $supplier1,
                    'product_code' => 'TEST-OMEP-20',
                    'discount' => 0,
                ],
            ];

            foreach ($medicines as $item) {

                /*
                 * -----------------------------------------------------
                 * 3A. Medicament
                 * -----------------------------------------------------
                 */
                $medicament = DB::table('medicaments')
                    ->where('bar_code', $item['barcode'])
                    ->first();

                if (!$medicament) {
                    $medicamentId = DB::table('medicaments')->insertGetId([
                        'name' => $item['name'],
                        'active_substance' => $item['active_substance'],
                        'bar_code' => $item['barcode'],
                        'price' => $item['price'],
                        'recipe_required' => $item['recipe_required'],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                } else {
                    $medicamentId = $medicament->id;
                }

                /*
                 * -----------------------------------------------------
                 * 3B. Goods / Stock
                 * -----------------------------------------------------
                 */
                $goods = DB::table('goods')
                    ->where('pharmacy_id', $pharmacy->id)
                    ->where('medicament_id', $medicamentId)
                    ->first();

                if ($goods) {
                    DB::table('goods')
                        ->where('id', $goods->id)
                        ->update([
                            'quantity' => $item['quantity'],
                            'price' => $item['purchase_price'],
                            'updated_at' => now(),
                        ]);
                } else {
                    DB::table('goods')->insert([
                        'pharmacy_id' => $pharmacy->id,
                        'medicament_id' => $medicamentId,
                        'quantity' => $item['quantity'],
                        'price' => $item['purchase_price'],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                /*
                 * -----------------------------------------------------
                 * 3C. Purchase
                 * -----------------------------------------------------
                 */
                $purchase = Purchase::withoutGlobalScopes()->firstOrCreate(
                    [
                        'name' => $item['name'] . ' - Pharmacy ' . $pharmacy->id,
                    ],
                    [
                        'category_id' => $categories[$item['category']]->id,
                        'supplier_id' => $item['supplier']->id,
                        'price' => $item['purchase_price'],
                        'quantity' => $item['quantity'],
                        'expiry_date' => $item['expiry'],
                        'pharmacy_id' => $pharmacy->id,
                    ]
                );

                /*
                 * -----------------------------------------------------
                 * 3D. Product
                 * -----------------------------------------------------
                 */
                Product::withoutGlobalScopes()->updateOrCreate(
                    [
                        'product_code' => $item['product_code'],
                    ],
                    [
                        'purchase_id' => $purchase->id,
                        'price' => $item['price'],
                        'discount' => $item['discount'],
                        'description' => $item['name'] . ' demo stock',
                        'pharmacy_id' => $pharmacy->id,
                    ]
                );
            }
        });
    }
}
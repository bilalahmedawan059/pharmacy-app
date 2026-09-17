<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Pharmacy;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $user = User::updateOrCreate(
            ['email' => 'contact@cdlcell.com'],
            [
                'name' => 'CDL',
                'password' => Hash::make('cdlcell'),
            ]
        );
        $user->syncRoles(['super-admin']);

        $pharmacy = Pharmacy::withoutGlobalScopes()->updateOrCreate(
            ['tax_type' => 'NTN', 'tax_id' => '1234567'],
            [
                'business_name' => 'Demo Pharmacy',
                'owner_name' => 'Demo Pharmacy Owner',
                'owner_email' => 'owner@demo-pharmacy.test',
                'address' => 'Main Market, Islamabad',
                'contact_address' => 'Main Market, Islamabad',
                'city' => 'Islamabad',
                'phone_number' => '03000000000',
                'status' => 'active',
                'is_manufacturing' => false,
                'max_employees' => 50,
                'launched_at' => now(),
            ]
        );

        $mainBranch = Branch::updateOrCreate(
            ['license_number' => 'DEMO-ISB-001'],
            [
                'pharmacy_id' => $pharmacy->id,
                'name' => 'Main Branch',
                'address' => 'Main Market, Islamabad',
                'city' => 'Islamabad',
                'contact' => '03000000001',
                'status' => 'active',
            ]
        );

        $secondBranch = Branch::updateOrCreate(
            ['license_number' => 'DEMO-RWP-001'],
            [
                'pharmacy_id' => $pharmacy->id,
                'name' => 'Rawalpindi Branch',
                'address' => 'Saddar, Rawalpindi',
                'city' => 'Rawalpindi',
                'contact' => '03000000002',
                'status' => 'active',
            ]
        );

        $this->seedPharmacyUser($pharmacy, $mainBranch, 'admin@demo-pharmacy.test', 'Pharmacy Admin', 'admin');
        $this->seedPharmacyUser($pharmacy, $mainBranch, 'manager@demo-pharmacy.test', 'Main Branch Manager', 'branch-manager');
        $this->seedPharmacyUser($pharmacy, $mainBranch, 'pharmacist@demo-pharmacy.test', 'Main Pharmacist', 'pharmacist');
        $this->seedPharmacyUser($pharmacy, $secondBranch, 'salesman@demo-pharmacy.test', 'Rawalpindi Salesman', 'salesman');
        $this->seedPharmacyUser($pharmacy, $secondBranch, 'cashier@demo-pharmacy.test', 'Rawalpindi Cashier', 'cashier');
    }

    private function seedPharmacyUser(Pharmacy $pharmacy, Branch $branch, $email, $name, $role)
    {
        $user = User::updateOrCreate(
            ['email' => $email],
            [
                'pharmacy_id' => $pharmacy->id,
                'branch_id' => $branch->id,
                'name' => $name,
                'phone' => $branch->contact,
                'password' => Hash::make('password'),
                'is_invited' => false,
            ]
        );

        $user->syncRoles([$role]);
    }
}

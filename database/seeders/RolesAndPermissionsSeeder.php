<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
      app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

      $permissionNames = [
        'view-dashboard', 'view-branches', 'create-branch', 'update-branch', 'destroy-branch',
        'view-sales', 'create-sales', 'update-sales', 'destroy-sale',
        'view-reports',
        'view-category', 'create-category', 'update-category', 'destroy-category',
        'view-products', 'create-product', 'update-product', 'destroy-product',
        'view-expired-products', 'view-outstock-products',
        'view-purchase', 'create-purchase', 'update-purchase', 'destroy-purchase',
        'view-supplier', 'create-supplier', 'update-supplier', 'destroy-supplier',
        'view-users', 'create-user', 'update-user', 'destroy-user',
        'view-access-control',
        'view-role', 'create-role', 'update-role', 'destroy-role',
        'view-permission', 'create-permission', 'update-permission', 'destroy-permission',
        'view-settings', 'backup-app', 'backup-db', 'view-backups', 'create-backup', 'download-backup', 'delete-backup',
        'view-profile', 'update-profile', 'update-password', 'view-notifications',
      ];

      foreach (array_unique($permissionNames) as $permissionName) {
        Permission::firstOrCreate([
          'name' => $permissionName,
          'guard_name' => 'web',
        ]);
      }

      $allPermissions = Permission::where('guard_name', 'web')->pluck('name')->all();
      $rolePermissions = [
        'super-admin' => $allPermissions,
        'admin' => [
          'view-dashboard', 'view-branches', 'create-branch', 'update-branch', 'destroy-branch',
          'view-sales', 'create-sales', 'update-sales', 'destroy-sale', 'view-reports',
          'view-category', 'create-category', 'update-category', 'destroy-category',
          'view-products', 'create-product', 'update-product', 'destroy-product',
          'view-expired-products', 'view-outstock-products',
          'view-purchase', 'create-purchase', 'update-purchase', 'destroy-purchase',
          'view-supplier', 'create-supplier', 'update-supplier', 'destroy-supplier',
          'view-users', 'create-user', 'update-user', 'destroy-user',
          'view-settings', 'view-profile', 'update-profile', 'update-password', 'view-notifications', 'update-role',
        ],
        'branch-manager' => [
          'view-dashboard', 'view-branches', 'view-sales', 'create-sales', 'update-sales',
          'view-reports', 'view-products', 'view-expired-products', 'view-outstock-products',
          'view-purchase', 'create-purchase', 'update-purchase', 'view-supplier',
          'view-users', 'view-profile', 'update-profile', 'update-password', 'view-notifications',
        ],
        'pharmacist' => [
          'view-dashboard', 'view-products', 'create-product', 'update-product',
          'view-expired-products', 'view-outstock-products', 'view-purchase', 'view-sales',
          'create-sales', 'view-reports', 'view-profile', 'update-profile', 'update-password',
        ],
        'salesman' => [
          'view-dashboard', 'view-sales', 'create-sales', 'view-products', 'view-reports',
          'view-profile', 'update-profile', 'update-password',
        ],
        'cashier' => [
          'view-dashboard', 'view-sales', 'create-sales', 'view-products',
          'view-profile', 'update-profile', 'update-password',
        ],
        'inventory-manager' => [
          'view-dashboard', 'view-products', 'create-product', 'update-product', 'destroy-product',
          'view-expired-products', 'view-outstock-products', 'view-purchase', 'create-purchase',
          'update-purchase', 'destroy-purchase', 'view-supplier', 'create-supplier',
          'update-supplier', 'destroy-supplier', 'view-profile', 'update-profile', 'update-password',
        ],
        'report-viewer' => [
          'view-dashboard', 'view-reports', 'view-sales', 'view-products', 'view-purchase',
          'view-supplier', 'view-profile', 'update-profile', 'update-password',
        ],
        'sales-person' => ['view-dashboard', 'view-sales', 'create-sales', 'view-reports', 'view-profile', 'update-profile', 'update-password'],
      ];

      foreach ($rolePermissions as $roleName => $permissions) {
        $role = Role::withoutGlobalScopes()->firstOrCreate([
          'name' => $roleName,
          'guard_name' => 'web',
          'pharmacy_id' => null,
        ]);
        $role->syncPermissions($permissions);
      }

      app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }
}

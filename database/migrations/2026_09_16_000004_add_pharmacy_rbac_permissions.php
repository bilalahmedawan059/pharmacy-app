<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AddPharmacyRbacPermissions extends Migration
{
    public function up()
    {
        $permissions = ['view-dashboard', 'view-branches', 'create-branch'];
        foreach ($permissions as $permissionName) {
            $permission = Permission::firstOrCreate([
                'name' => $permissionName,
                'guard_name' => 'web',
            ]);
            Role::where('name', 'super-admin')->get()->each(function ($role) use ($permission) {
                $role->givePermissionTo($permission);
            });
            Role::where('name', 'sales-person')->get()->each(function ($role) use ($permissionName, $permission) {
                if ($permissionName === 'view-dashboard') {
                    $role->givePermissionTo($permission);
                }
            });
        }
    }

    public function down()
    {
        Permission::whereIn('name', ['view-dashboard', 'view-branches', 'create-branch'])->delete();
    }
}
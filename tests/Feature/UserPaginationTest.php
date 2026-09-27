<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class UserPaginationTest extends TestCase
{
    use RefreshDatabase;

    public function test_single_page_user_list_still_shows_pagination_status(): void
    {
        $admin = $this->createSuperAdmin();

        $this->actingAs($admin)->get(route('users'))
            ->assertOk()
            ->assertSee('Showing 1 to 1 of 1 records')
            ->assertSee('Page 1 of 1')
            ->assertSee('Role Management')
            ->assertSee('Permissions')
            ->assertSee('aria-label="Previous"', false)
            ->assertSee('aria-label="Next"', false);
    }

    public function test_role_and_permission_sidebar_links_are_hidden_from_non_super_admins(): void
    {
        $permission = Permission::firstOrCreate([
            'name' => 'view-users',
            'guard_name' => 'web',
        ]);
        $role = Role::firstOrCreate([
            'name' => 'sales-person',
            'guard_name' => 'web',
            'pharmacy_id' => null,
        ]);
        $role->givePermissionTo($permission);
        $user = User::factory()->create();
        $user->assignRole($role);

        $this->actingAs($user)->get(route('users'))
            ->assertOk()
            ->assertDontSee('Role Management')
            ->assertDontSee('Permissions');
    }

    public function test_user_list_paginates_and_navigates_to_the_next_page(): void
    {
        $admin = $this->createSuperAdmin();
        for ($index = 1; $index <= 11; $index++) {
            User::factory()->create(['name' => sprintf('Person %02d', $index)]);
        }

        $this->actingAs($admin)->get(route('users'))
            ->assertOk()
            ->assertSee('Showing 1 to 10 of 12 records')
            ->assertSee('Page 1 of 2')
            ->assertSee('Person 01')
            ->assertDontSee('Person 11');

        $this->get(route('users', ['page' => 2]))
            ->assertOk()
            ->assertSee('Showing 11 to 12 of 12 records')
            ->assertSee('Page 2 of 2')
            ->assertSee('Person 11')
            ->assertDontSee('Person 01');
    }

    private function createSuperAdmin(): User
    {
        $permission = Permission::firstOrCreate([
            'name' => 'view-users',
            'guard_name' => 'web',
        ]);
        $role = Role::firstOrCreate([
            'name' => 'super-admin',
            'guard_name' => 'web',
            'pharmacy_id' => null,
        ]);
        $role->givePermissionTo($permission);

        $admin = User::factory()->create(['name' => 'Admin']);
        $admin->assignRole($role);

        return $admin;
    }
}
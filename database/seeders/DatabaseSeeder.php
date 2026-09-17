<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Database\Seeders\UserSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\DemoDataSeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call([
            DeleteStorageDataSeeder::class,
            RolesAndPermissionsSeeder::class,
            UserSeeder::class,
            DemoDataSeeder::class,
        ]);
    }
}

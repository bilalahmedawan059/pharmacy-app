<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class DeleteStorageDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $directoryPath = ['public/users', 'public/purchases', 'public/products'];

        foreach ($directoryPath as $dir) {
            Storage::deleteDirectory($dir);
        }

        $this->command->info("Files in storage deleted successfully.");

    }
}

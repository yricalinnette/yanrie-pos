<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Call the Yanrie POS store catalog parameters
        $this->call(YanrieStoreSeeder::class);
    }
}
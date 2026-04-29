<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            SettingsSeeder::class,
            UsersSeeder::class,
            CategoriesSeeder::class,
            ProductsSeeder::class,
            FarmersSeeder::class,
            DemoMarketSeeder::class,
        ]);
    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            AdminSeeder::class,
            DeliveryZonesSeeder::class,       // Tambahkan ini
            DriversSeeder::class,             // Tambahkan ini
            LaundrySeeder::class,             // Tambahkan ini
            LaundryServiceSeeder::class,      // Tambahkan ini
            ProductSeeder::class,             // Tambahkan ini
            // Seeder lainnya
        ]);
    }
}
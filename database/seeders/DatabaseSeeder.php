<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
<<<<<<< HEAD
    public function run()
    {
        $this->call([
            OutletSeeder::class,
            UserSeeder::class,
=======
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
>>>>>>> f060a5238c9be001064f299807a527823f9b6ff1
        ]);
    }
}
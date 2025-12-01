<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        // Cek apakah sudah ada admin
        $adminExists = DB::table('admin')->where('email', 'admin@gmail.com')->exists();

        if (!$adminExists) {
            DB::table('admin')->insert([
                [
                    'nama' => 'Admin Utama',
                    'email' => 'admin@gmail.com',
                    'password' => Hash::make('adminlaundry'),
                    'no_handphone' => '08123456789',
                    'alamat' => 'Jl. Admin Utama No. 1',
                    'role' => 'admin',
                    'created_at' => now(),
                    'updated_at' => now()
                ],
                [
                    'nama' => 'Staff 1',
                    'email' => 'staff1@gmail.com',
                    'password' => Hash::make('stafflaundry1'),
                    'no_handphone' => '08234567890',
                    'alamat' => 'Jl. Staff 1 No. 2',
                    'role' => 'staff',
                    'created_at' => now(),
                    'updated_at' => now()
                ],
                [
                    'nama' => 'Staff 2',
                    'email' => 'staff2@gmail.com',
                    'password' => Hash::make('stafflaundry2'),
                    'no_handphone' => '08345678901',
                    'alamat' => 'Jl. Staff 2 No. 3',
                    'role' => 'staff',
                    'created_at' => now(),
                    'updated_at' => now()
                ]
            ]);

            $this->command->info('Admin data seeded successfully!');
        } else {
            $this->command->info('Admin data already exists.');
        }
    }
}
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB; // Harus di-import

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Masukkan data hanya jika tabel 'products' masih kosong
        if (DB::table('products')->count() == 0) {
            DB::table('products')->insert([
                [
                    // Pastikan nama kolom sama persis dengan migrasi
                    'name' => 'Paket Cuci Kering Super',
                    'price' => 50000.00,
                    'description' => 'Paket cuci kering premium dengan pewangi khusus.',
                    'image' => 'cuci_kering.jpg',
                    'duration_days' => 3,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'name' => 'Layanan Setrika Cepat',
                    'price' => 25000.00,
                    'description' => 'Hanya setrika, selesai dalam 1 hari.',
                    'image' => 'setrika.jpg',
                    'duration_days' => 1,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'name' => 'Pembersih Sepatu',
                    'price' => 45000.00,
                    'description' => 'Layanan khusus untuk sepatu dan tas.',
                    'image' => null, // Boleh null jika tidak ada gambar
                    'duration_days' => 5,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            ]);
            echo "Produk awal berhasil di-seed!\n";
        } else {
            echo "Tabel products sudah berisi data.\n";
        }
    }
}
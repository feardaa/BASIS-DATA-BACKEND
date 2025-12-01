<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LaundryServiceSeeder extends Seeder
{
    public function run(): void
    {
        $services = [
            // Pakaian
            [
                'nama_service' => 'Cuci Kering Regular',
                'kategori' => 'pakaian',
                'harga' => 7000,
                'tipe_harga' => 'per_kg',
                'estimasi_hari' => 2
            ],
            [
                'nama_service' => 'Cuci Setrika Regular',
                'kategori' => 'pakaian',
                'harga' => 10000,
                'tipe_harga' => 'per_kg',
                'estimasi_hari' => 2
            ],
            [
                'nama_service' => 'Setrika Saja',
                'kategori' => 'pakaian',
                'harga' => 5000,
                'tipe_harga' => 'per_kg',
                'estimasi_hari' => 1
            ],

            // Sepatu
            [
                'nama_service' => 'Cuci Sepatu Regular',
                'kategori' => 'sepatu',
                'harga' => 25000,
                'tipe_harga' => 'per_item',
                'estimasi_hari' => 3
            ],
            [
                'nama_service' => 'Cuci Sepatu Premium',
                'kategori' => 'sepatu',
                'harga' => 40000,
                'tipe_harga' => 'per_item',
                'estimasi_hari' => 3
            ],

            // Tas
            [
                'nama_service' => 'Cuci Tas Regular',
                'kategori' => 'tas',
                'harga' => 30000,
                'tipe_harga' => 'per_item',
                'estimasi_hari' => 4
            ],
            [
                'nama_service' => 'Cuci Tas Premium',
                'kategori' => 'tas',
                'harga' => 50000,
                'tipe_harga' => 'per_item',
                'estimasi_hari' => 4
            ],

            // Karpet
            [
                'nama_service' => 'Cuci Karpet Small',
                'kategori' => 'karpet',
                'harga' => 50000,
                'tipe_harga' => 'per_item',
                'estimasi_hari' => 5
            ],
            [
                'nama_service' => 'Cuci Karpet Large',
                'kategori' => 'karpet',
                'harga' => 80000,
                'tipe_harga' => 'per_item',
                'estimasi_hari' => 5
            ],
        ];

        foreach ($services as $service) {
            DB::table('laundry_services')->updateOrInsert(
                ['nama_service' => $service['nama_service']],
                $service
            );
        }
    }
}
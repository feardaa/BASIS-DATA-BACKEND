<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LaundrySeeder extends Seeder
{
    public function run()
    {
        DB::table('laundry_services')->insert([
            ['nama_service'=>'Cuci Kering','kategori'=>'pakaian','harga'=>7000.00,'tipe_harga'=>'per_kg','estimasi_hari'=>2],
            ['nama_service'=>'Cuci Sepatu','kategori'=>'sepatu','harga'=>25000.00,'tipe_harga'=>'per_item','estimasi_hari'=>3],
        ]);
    }
}

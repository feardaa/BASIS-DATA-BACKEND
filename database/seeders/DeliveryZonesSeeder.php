<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DeliveryZonesSeeder extends Seeder
{
    public function run()
    {
        DB::table('delivery_zones')->insert([
            ['nama_zone'=>'Zone 1','ongkir'=>5000,'estimasi_jam'=>2,'status'=>'aktif'],
            ['nama_zone'=>'Zone 2','ongkir'=>10000,'estimasi_jam'=>3,'status'=>'aktif'],
        ]);
    }
}

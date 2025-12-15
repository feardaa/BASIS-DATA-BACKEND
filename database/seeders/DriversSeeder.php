<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DriversSeeder extends Seeder
{
    public function run()
    {
        DB::table('drivers')->insert([
            ['nama'=>'Joko','no_handphone'=>'081234567890','kendaraan'=>'Motor','status'=>'aktif'],
            ['nama'=>'Slamet','no_handphone'=>'082345678901','kendaraan'=>'Mobil','status'=>'aktif'],
        ]);
    }
}

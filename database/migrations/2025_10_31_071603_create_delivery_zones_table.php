<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDeliveryZonesTable extends Migration
{
    public function up()
    {
        Schema::create('delivery_zones', function (Blueprint $table) {
            // PERBAIKAN PENTING: Mengganti increments() (UNSIGNED INT) 
            // dengan id() (UNSIGNED BIGINT) agar cocok dengan foreignId() di tabel orders.
            $table->id('id_zone'); // Menggunakan helper id() untuk UNSIGNED BIGINT
            
            $table->string('nama_zone', 100);
            $table->decimal('ongkir', 10, 2); // Menggunakan nama kolom dari kode Anda
            $table->integer('estimasi_jam'); // Menggunakan kolom dari kode Anda
            $table->enum('status', ['aktif','nonaktif'])->default('aktif');
            
            $table->timestamps(); // Tambahkan timestamps agar standar
        });
    }

    public function down()
    {
        Schema::dropIfExists('delivery_zones');
    }
}
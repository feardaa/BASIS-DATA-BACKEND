<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDriversTable extends Migration
{
    public function up()
    {
        Schema::create('drivers', function (Blueprint $table) {
            // PERBAIKAN UTAMA: Mengganti increments() (UNSIGNED INT) 
            // dengan id() (UNSIGNED BIGINT) agar cocok dengan foreignId() di tabel orders.
            $table->id('id_driver'); 
            
            $table->string('nama',100);
            $table->string('no_handphone',15);
            $table->string('kendaraan',50);
            $table->enum('status',['aktif','nonaktif'])->default('aktif');
            // Tambahkan timestamps agar standar dan bisa digunakan untuk sorting
            $table->timestamps(); 
        });
    }

    public function down()
    {
        Schema::dropIfExists('drivers');
    }
}
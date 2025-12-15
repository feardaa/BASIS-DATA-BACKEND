<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLaundryServicesTable extends Migration
{
    public function up()
    {
        Schema::create('laundry_services', function (Blueprint $table) {
            $table->increments('id_service');
            $table->string('nama_service',100);
            $table->enum('kategori',['pakaian','sepatu','tas','karpet','kering','setrika']);
            $table->decimal('harga',10,2);
            $table->enum('tipe_harga',['per_kg','per_item'])->default('per_kg');
            $table->integer('estimasi_hari');
        });
    }

    public function down()
    {
        Schema::dropIfExists('laundry_services');
    }
}

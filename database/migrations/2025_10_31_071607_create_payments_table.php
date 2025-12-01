<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePaymentsTable extends Migration
{
    public function up()
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->increments('id_payment');
            $table->unsignedInteger('id_order');
            $table->enum('metode',['cash','transfer','ewallet']);
            $table->decimal('jumlah',10,2);
            $table->enum('status',['ditunda','lunas','gagal'])->default('ditunda');
            $table->timestamp('tanggal_bayar')->nullable();
            $table->string('bukti')->nullable(); // tambahan untuk path bukti transfer

            $table->index('id_order');
            $table->foreign('id_order')->references('id_order')->on('orders')->onDelete('cascade')->onUpdate('cascade');
        });
    }

    public function down()
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['id_order']);
        });
        Schema::dropIfExists('payments');
    }
}

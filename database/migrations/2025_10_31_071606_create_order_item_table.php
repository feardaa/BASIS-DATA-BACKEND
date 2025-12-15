<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrderItemTable extends Migration
{
    public function up()
    {
        Schema::create('order_item', function (Blueprint $table) {
            $table->increments('id_item');
            $table->unsignedInteger('id_order');
            $table->unsignedInteger('id_service');
            $table->integer('jumlah')->default(0);
            $table->decimal('berat_kg',5,2)->default(0.00);
            $table->decimal('subtotal',10,2);

            $table->index('id_order');
            $table->index('id_service');

            $table->foreign('id_order')->references('id_order')->on('orders')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('id_service')->references('id_service')->on('laundry_services')->onUpdate('cascade');
        });
    }

    public function down()
    {
        Schema::table('order_item', function (Blueprint $table) {
            $table->dropForeign(['id_order']);
            $table->dropForeign(['id_service']);
        });
        Schema::dropIfExists('order_item');
    }
}

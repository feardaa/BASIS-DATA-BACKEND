<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReviewsTable extends Migration
{
    public function up()
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->increments('id_review');
            $table->unsignedInteger('id_order');
            $table->integer('rating');
            $table->text('komentar');
            $table->timestamp('tanggal_review')->useCurrent();

            $table->index('id_order');
            $table->foreign('id_order')->references('id_order')->on('orders')->onDelete('cascade')->onUpdate('cascade');
        });
    }

    public function down()
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropForeign(['id_order']);
        });
        Schema::dropIfExists('reviews');
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrdersTable extends Migration
{
    public function up()
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->increments('id_order');

            // PERBAIKAN PENTING: Mengubah tipe data kunci asing agar cocok dengan Primary Key (yang biasanya unsignedBigInteger)
            // Menggunakan foreignId() adalah cara termudah dan teraman untuk memastikan kecocokan tipe data.
            $table->foreignId('id_user')->constrained('users', 'id_users')->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('id_driver')->nullable()->constrained('drivers', 'id_driver')->onUpdate('cascade')->onDelete('set null');
            $table->foreignId('id_zone')->constrained('delivery_zones', 'id_zone')->onUpdate('cascade')->onDelete('cascade');

            // Kolom-kolom lainnya
            $table->enum('status', ['menunggu', 'dijemput', 'diproses', 'selesai', 'dibatalkan'])->default('menunggu');
            $table->timestamp('tanggal_pesan')->useCurrent();
            $table->timestamp('tanggal_selesai')->nullable();

            // Catatan: Jika menggunakan foreignId().constrained(...), index sudah otomatis dibuat. 
            // Baris $table->index(...) tidak lagi diperlukan.
        });
    }

    public function down()
    {
        // Drop Foreign Keys
        Schema::table('orders', function (Blueprint $table) {
            // Drop foreignId keys menggunakan nama konvensi Laravel (table_column_foreign)
            $table->dropForeign(['id_users']);
            $table->dropForeign(['id_driver']);
            $table->dropForeign(['id_zone']);
        });
        // Drop Table
        Schema::dropIfExists('orders');
    }
}
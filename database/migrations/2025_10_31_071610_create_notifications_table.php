<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNotificationsTable extends Migration
{
    public function up()
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->increments('id_notif');
            
            // PERBAIKAN 1: Mengganti unsignedInteger dengan foreignId() agar cocok (UNSIGNED BIGINT).
            // PERBAIKAN 2: Mengganti references ke tabel 'user' menjadi 'users' (nama tabel yang benar).
            // Catatan: Asumsi Primary Key di tabel users adalah 'id_users' (sesuai migrasi orders sebelumnya).
            $table->foreignId('id_user')->constrained('users', 'id_users')->onDelete('cascade')->onUpdate('cascade');
            
            $table->text('pesan');
            $table->enum('status',['terkirim','dibaca'])->default('terkirim');
            $table->timestamp('created_at')->useCurrent();
            
            // Catatan: Jika menggunakan foreignId().constrained(...), index sudah otomatis dibuat.
        });
    }

    public function down()
    {
        Schema::table('notifications', function (Blueprint $table) {
            // Drop foreign key harus menggunakan nama kolom
            $table->dropForeign(['id_user']);
        });
        Schema::dropIfExists('notifications');
    }
}
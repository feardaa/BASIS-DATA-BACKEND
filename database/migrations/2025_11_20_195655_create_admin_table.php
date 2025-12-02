<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAdminTable extends Migration {
    public function up(): void
    {
<<<<<<< HEAD
        // Drop tabel admin jika sudah ada (agar bisa di-migrate ulang)
=======
        // Drop tabel admin jika sudah ada
>>>>>>> 377b9e62b633522013f8e0176f08e4f8016b95bd
        Schema::dropIfExists('admin');

        // Membuat tabel admin
        Schema::create('admin', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 100);
            $table->string('email', 100)->unique();
            $table->string('password', 255);
<<<<<<< HEAD
            // ======= PERBAIKAN: TAMBAHKAN DUA KOLOM INI =======
            $table->string('no_handphone', 15)->nullable();
            $table->text('alamat')->nullable();
            // =================================================
            $table->enum('role', ['admin', 'staff'])->default('admin', 'staff');
=======
            $table->enum('role', ['admin', 'staff'])->default('staff');
            
            // --- PERUBAHAN KRITIS (Menambahkan Kolom yang Dibutuhkan Seeder) ---
            $table->string('no_handphone', 15)->nullable(); // Tambahkan kolom no_handphone
            $table->text('alamat')->nullable();              // Tambahkan kolom alamat
            // -------------------------------------------------------------------
            
>>>>>>> 377b9e62b633522013f8e0176f08e4f8016b95bd
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin');
    }
};
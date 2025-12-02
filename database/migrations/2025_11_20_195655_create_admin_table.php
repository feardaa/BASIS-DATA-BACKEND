<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAdminTable extends Migration {
    public function up(): void
    {
        // Drop tabel admin jika sudah ada
        Schema::dropIfExists('admin');

        // Membuat tabel admin
        Schema::create('admin', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 100);
            $table->string('email', 100)->unique();
            $table->string('password', 255);
            $table->enum('role', ['admin', 'staff'])->default('staff');
            
            // --- PERUBAHAN KRITIS (Menambahkan Kolom yang Dibutuhkan Seeder) ---
            $table->string('no_handphone', 15)->nullable(); // Tambahkan kolom no_handphone
            $table->text('alamat')->nullable();              // Tambahkan kolom alamat
            // -------------------------------------------------------------------
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin');
    }
};
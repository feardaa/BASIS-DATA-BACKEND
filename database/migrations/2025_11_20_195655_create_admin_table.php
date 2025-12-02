<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Drop tabel admin jika sudah ada (agar bisa di-migrate ulang)
        Schema::dropIfExists('admin');

        // Membuat tabel admin
        Schema::create('admin', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 100);
            $table->string('email', 100)->unique();
            $table->string('password', 255);
            // ======= PERBAIKAN: TAMBAHKAN DUA KOLOM INI =======
            $table->string('no_handphone', 15)->nullable();
            $table->text('alamat')->nullable();
            // =================================================
            $table->enum('role', ['admin', 'staff'])->default('admin', 'staff');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin');
    }
};
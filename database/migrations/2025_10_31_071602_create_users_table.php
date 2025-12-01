<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Drop tabel user jika sudah ada
        Schema::dropIfExists('users');

        // Buat tabel user dengan struktur yang benar
        Schema::create('users', function (Blueprint $table) {
            $table->id('id_users');
            $table->string('nama', 100);
            $table->string('email', 100)->unique();
            $table->string('password', 255);
            $table->string('no_handphone', 15);
            $table->text('alamat');
            $table->timestamps(); // created_at dan updated_at
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
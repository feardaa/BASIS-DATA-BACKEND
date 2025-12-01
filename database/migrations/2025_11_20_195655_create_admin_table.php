<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Drop tabel admin jika sudah ada
        Schema::dropIfExists('admin'); // PERUBAHAN: 'admin'

        // Membuat tabel admin
        Schema::create('admin', function (Blueprint $table) { // PERUBAHAN: 'admin'
            $table->id();
            $table->string('nama', 100);
            $table->string('email', 100)->unique();
            $table->string('password', 255);
            $table->enum('role', ['admin', 'staff'])->default('staff');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin'); // PERUBAHAN: 'admin'
    }
};
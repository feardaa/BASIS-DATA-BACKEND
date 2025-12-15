<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// PASTIKAN NAMA CLASS INI SESUAI DENGAN YANG DIBUAT LARAVEL
class AddTimestampsToAdminTableFinal extends Migration 
{
    public function up()
    {
        Schema::table('admin', function (Blueprint $table) {
            // Hanya tambahkan jika kolom belum ada (praktik yang baik)
            if (!Schema::hasColumn('admin', 'created_at')) {
                $table->timestamps();
            }
        });
    }

    public function down()
    {
        Schema::table('admin', function (Blueprint $table) {
            $table->dropTimestamps();
        });
    }
}
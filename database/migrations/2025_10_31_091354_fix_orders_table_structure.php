<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class FixOrdersTableStructure extends Migration
{
    public function up()
    {
        Schema::table('orders', function (Blueprint $table) {
            // Cek dan tambahkan kolom jika tidak ada
            if (!Schema::hasColumn('orders', 'tanggal_pesan')) {
                $table->datetime('tanggal_pesan')->nullable()->after('id_zone');
            }
            if (!Schema::hasColumn('orders', 'tanggal_selesai')) {
                $table->datetime('tanggal_selesai')->nullable()->after('tanggal_pesan');
            }
            if (!Schema::hasColumn('orders', 'created_at')) {
                $table->timestamps();
            }
        });
    }

    public function down()
    {
        // Optional: rollback logic
    }
}
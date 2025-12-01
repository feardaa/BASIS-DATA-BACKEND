<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LaundryService extends Model
{
    use HasFactory;

    protected $primaryKey = 'id_laundry_service';
    protected $table = 'laundry_services';
    public $timestamps = false; // Asumsi tidak ada timestamps

    protected $fillable = [
        'nama_layanan',
        'harga_per_kg',
        'deskripsi',
    ];
}
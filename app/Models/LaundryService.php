<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LaundryService extends Model
{
    use HasFactory;

    // Primary Key disesuaikan
    protected $primaryKey = 'id_service'; 
    protected $table = 'laundry_services';
    public $timestamps = false; // Disesuaikan

    // Kolom fillable disesuaikan
    protected $fillable = [
        'nama_service', 
        'kategori', 
        'harga', 
        'tipe_harga', 
        'estimasi_hari',
    ];
}
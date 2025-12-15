<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeliveryZone extends Model
{
    use HasFactory;

    protected $table = 'delivery_zones';
    protected $primaryKey = 'id_zone';
    
    // Harus TRUE (atau dihapus) agar sesuai dengan kolom created_at/updated_at di DB
    public $timestamps = true; 

    protected $fillable = [
        'nama_zone',
        'ongkir',
        'estimasi_jam', // Ditambahkan agar bisa di-POST/di-SEED
        'status'
    ];
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;
<<<<<<< HEAD

    protected $fillable = [
        'id_outlet',
        'nama',
        'jenis',
        'satuan',
        'harga'
    ];
}
=======
    
    // 1. Definisikan nama tabel secara eksplisit
    protected $table = 'products';

    // 2. Definisikan Primary Key non-standar (asumsi: id_product)
    protected $primaryKey = 'id_product';

    protected $fillable = [
        'name',
        'price', 
        'description',
        'image', // Asumsi: Menyimpan path atau URL gambar
        'duration_days', // Jika ini adalah layanan yang memiliki durasi
        'is_active'
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_active' => 'boolean'
    ];
    

}
>>>>>>> f060a5238c9be001064f299807a527823f9b6ff1

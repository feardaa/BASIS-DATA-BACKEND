<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory; // Tambahkan ini

class Review extends Model
{
    use HasFactory; // Gunakan trait ini

    protected $table = 'reviews';
    protected $primaryKey = 'id_review';
    
    // Asumsi: Anda mengisi 'tanggal_review' secara manual, 
    // jadi kita pertahankan timestamps = false.
    public $timestamps = false; 

    protected $fillable = [
        'id_order', 
        'rating', 
        'komentar', 
        'tanggal_review'
    ];
    
    // Wajib: Tambahkan casting untuk menjamin tipe data dan format tanggal
    protected $casts = [
        'rating' => 'integer', 
        'tanggal_review' => 'datetime',
    ];

    /**
     * Relasi: Review dimiliki oleh satu Order.
     */
    public function order()
    {
        // Pastikan foreign key dan local key sudah benar
        return $this->belongsTo(Order::class, 'id_order', 'id_order');
    }
}
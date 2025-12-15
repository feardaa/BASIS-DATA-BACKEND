<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $table = 'payments';
    protected $primaryKey = 'id_payment';
    public $timestamps = false; 

    protected $fillable = [
        'id_order',
        'metode',
        'jumlah',
        'status',
        'tanggal_bayar'
    ];

    protected $casts = [
        'jumlah' => 'decimal:2',
        'tanggal_bayar' => 'datetime',
    ];

    /**
     * Relasi: Payment terhubung ke satu Order.
     */
    public function order()
    {
        // Pastikan foreign key dan local key sudah benar
        return $this->belongsTo(Order::class, 'id_order', 'id_order');
    }
}
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

    // Relasi ke order
    public function order()
    {
        return $this->belongsTo(Order::class, 'id_order', 'id_order');
    }
}
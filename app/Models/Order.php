<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $table = 'orders';
    protected $primaryKey = 'id_order';
    public $timestamps = true;

    protected $fillable = [
        'id_user',
        'id_admin',
        'id_driver',
        'id_zone',
        'status',
        'kategori_laundry',
        'berat_total',
        'tanggal_jemput',
        'catatan',
        'tanggal_pesan',
        'tanggal_selesai',
    ];

    protected $casts = [
        'kategori_laundry' => 'array',
        'berat_total' => 'decimal:2',
        'tanggal_pesan' => 'datetime',
        'tanggal_selesai' => 'datetime',
        'tanggal_jemput' => 'datetime',
    ];

    /**
     * Relasi: Order dimiliki oleh satu User (Pelanggan).
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'id_user', 'id_users');
    }

    /**
     * Relasi: Order memiliki banyak OrderItem.
     */
    public function items()
    {
        return $this->hasMany(OrderItem::class, 'id_order', 'id_order');
    }

    /**
     * Relasi ke Driver jika sudah terassign.
     */
    public function driver()
    {
        return $this->belongsTo(Driver::class, 'id_driver', 'id_driver');
    }

    /**
     * Relasi ke Zone pengiriman.
     */
    public function zone()
    {
        return $this->belongsTo(DeliveryZone::class, 'id_zone', 'id_zone');
    }

    /**
     * Accessor untuk nomor order yang formatted.
     */
    public function getNoOrderAttribute()
    {
        return 'ORD' . str_pad($this->id_order, 6, '0', STR_PAD_LEFT);
    }
}
<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
<<<<<<< HEAD

class OrderItem extends Model
{
    protected $fillable = [
        'order_id',
        'product_id',
        'berat',
        'qty',
        'subtotal'
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
=======
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\LaundryService;

class OrderItem extends Model
{
    use HasFactory;

    // Nama tabel di database
    protected $table = 'order_item';

    // Primary key untuk tabel ini
    // Dilihat dari migrasi, Primary Key-nya adalah 'id_item'
    protected $primaryKey = 'id_item';

    // Menambahkan timestamps karena OrderController akan menggunakannya
    public $timestamps = true;

    protected $fillable = [
        'id_order',
        'id_service',
        'jumlah', // Digunakan jika satuan Layanan adalah 'per_pcs'
        'berat_kg', // Digunakan jika satuan Layanan adalah 'per_kg'
        'subtotal'
    ];

    protected $casts = [
        'berat_kg' => 'decimal:2',
        'subtotal' => 'decimal:2'
    ];

    /**
     * Relasi: OrderItem termasuk dalam satu Order.
     */
    public function order()
    {
        return $this->belongsTo(Order::class, 'id_order', 'id_order');
    }

    /**
     * Relasi: OrderItem terhubung ke satu LaundryService.
     */
    public function service()
    {
        // Asumsi model LaundryService ada
        return $this->belongsTo(LaundryService::class, 'id_service', 'id_service');
    }
}
>>>>>>> f060a5238c9be001064f299807a527823f9b6ff1

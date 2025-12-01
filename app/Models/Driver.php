<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Driver extends Model
{
    protected $table = 'drivers';
    protected $primaryKey = 'id_driver';
    public $timestamps = true;
    
    protected $fillable = [
        'nama', 'no_handphone', 'plat_nomor', 'status_aktif'
    ];

    protected $casts = [
        'status_aktif' => 'boolean'
    ];

    public function orders()
    {
        return $this->hasMany(Order::class, 'id_driver', 'id_driver');
    }
}
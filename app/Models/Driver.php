<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Driver extends Model
{
    // Saya tambahkan import ini untuk menjaga kompatibilitas Laravel
    use HasFactory; 

    protected $table = 'drivers';
    protected $primaryKey = 'id_driver'; // Diperbarui
    public $timestamps = true;
    
    protected $fillable = [
        'nama', 
        'no_handphone', 
        'plat_nomor', 
        'status_aktif' // Diperbarui
    ];

    protected $casts = [
        'status_aktif' => 'boolean' // Diperbarui
    ];

    public function orders()
    {
        return $this->hasMany(Order::class, 'id_driver', 'id_driver');
    }
}
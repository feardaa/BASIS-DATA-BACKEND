<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeliveryZone extends Model
{
    use HasFactory;

    protected $table = 'delivery_zones';
    protected $primaryKey = 'id_zone';
    public $timestamps = false;

    protected $fillable = [
        'nama_zone',
        'ongkir',
        'status'
    ];
}
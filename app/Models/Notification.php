<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $table = 'notifications';
    protected $primaryKey = 'id_notif';
    public $timestamps = false; 

    // Hapus 'created_at' dari fillable.
    protected $fillable = [
        'id_user',
        'pesan',
        'status',
    ];

    public function users()
    {
        return $this->belongsTo(Users::class, 'id_user', 'id_users'); 
    }
}
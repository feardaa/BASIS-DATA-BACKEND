<?php

namespace App\Models; // !!! HARUS App\Models

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Users extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'users';
    protected $primaryKey = 'id_users';
    public $timestamps = true;

    protected $fillable = [
        'nama',
        'email',
        'password',
        'no_handphone',
        'alamat',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed', // Laravel akan otomatis hashing password saat disimpan
    ];

    /* RELATIONS */
    public function orders()
    {
        return $this->hasMany(Order::class, 'id_users', 'id_users');
    }
}
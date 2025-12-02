<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable; // Wajib untuk Notifikasi (Opsional)
use Laravel\Sanctum\HasApiTokens; // Wajib untuk generate token API

class Admin extends Authenticatable
{
    // Tambahkan trait Notifiable dan HasApiTokens agar bisa generate token
    use HasFactory, Notifiable, HasApiTokens;

    protected $table = 'admin';
    protected $primaryKey = 'id';
    public $timestamps = true;

    protected $fillable = [
        'nama',
        'email',
        'password',
        'no_handphone',
        'alamat',
        'role'
    ];

    protected $hidden = [
        'password',
        'remember_token', // Ditambahkan untuk standar otentikasi
    ];
    
    // Casting wajib: Hashing password secara otomatis
    protected $casts = [
        'password' => 'hashed', 
    ];
}
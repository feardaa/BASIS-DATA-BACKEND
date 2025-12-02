<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Users extends Authenticatable
{
    // Menggunakan trait untuk Factory, Notifikasi, dan API Tokens (Wajib untuk API/Otentikasi)
    use HasApiTokens, HasFactory, Notifiable;

    // Primary Key non-standar dan nama tabel
    protected $table = 'users';
    protected $primaryKey = 'id_users';
    public $timestamps = true;

    /**
     * Kolom yang dapat diisi secara massal (untuk CRUD: Register, Update Profile).
     */
    protected $fillable = [
        'nama',
        'email',
        'password',
        'no_handphone',
        'alamat',
        'role', // Wajib untuk membedakan jenis pengguna
    ];

    /**
     * Kolom yang harus disembunyikan saat dikirim sebagai respons JSON (Keamanan).
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Konversi tipe data otomatis (Casting).
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        // Wajib: Menggunakan 'hashed' agar password di-hash secara otomatis saat disimpan
        'password' => 'hashed',
    ];

    /*
    |--------------------------------------------------------------------------
    | RELATIONS
    |--------------------------------------------------------------------------
    */

    /**
     * Relasi: Satu User dapat memiliki banyak Order.
     */
    public function orders()
    {
        return $this->hasMany(Order::class, 'id_user', 'id_users');
    }
}
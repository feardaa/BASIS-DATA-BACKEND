<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Users extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 'users';
    // PENTING: Mendefinisikan Primary Key yang benar
    protected $primaryKey = 'id_users';
    public $timestamps = true;

    protected $fillable = [
        'nama',
        'email',
        'password',
        'no_handphone',
        'alamat'
    ];

    protected $hidden = [
        'password'
    ];
}
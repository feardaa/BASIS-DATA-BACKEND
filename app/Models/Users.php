<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Users extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 'users';
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
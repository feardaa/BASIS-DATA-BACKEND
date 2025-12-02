<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Admin extends Authenticatable
{
    use HasFactory;

    protected $table = 'admin'; // PERUBAHAN: 'admin' bukan 'admins'
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
        'password'
    ];
}
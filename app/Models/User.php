<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    // Define which fields can be mass assigned
    protected $fillable = [
        'name',
        'email',
        'password',
        'mobile_no',
        'session_id',
    ];

    // Hide sensitive fields when serializing
    protected $hidden = [
        'password',
    ];
}

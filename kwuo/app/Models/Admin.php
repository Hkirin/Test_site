<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;

class Admin extends  Authenticatable
{
    use HasApiTokens, Notifiable, SoftDeletes;
    protected $fillable = [
        "firstname", "lastname", "email", "phone", "password", "avatar", "username", "ban",
        "reference", "type", "verifed"
    ];

    protected $hidden = [
        'password', 'remember_token', 'ban', 'created_at', "updated_at",
        "deleted_at", "type"
    ];
}

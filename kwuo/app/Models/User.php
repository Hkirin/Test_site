<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'firstname', 'lastname', 'email', 'password', 'phone', "is_agent",
        'reference', 'ban', 'avatar', 'pin', 'is_verified', 'balance'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token', 'ban', 'created_at', "updated_at",
        'email_verified_at', 'pin', 'is_verified', "deleted_at", 
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function user_detail()
    {
        return $this->hasOne(\App\Models\UserDetail::class);
    }

    public function business()
    {
        return $this->hasMany(\App\Models\Business::class, 'user_id');
    }

    public function getBusiness($business){
        return $this->business()->where('reference', $business)->first();
    }

    public function wallet()
    {
        return $this->hasOne(\App\Models\Wallet::class, 'user_id');
    }
}

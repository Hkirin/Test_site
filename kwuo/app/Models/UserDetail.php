<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserDetail extends Model
{
    protected $fillable = [
        'user_id', 'dob', 'address', 'bvn', 'country', 'state', 'lga', 'city', 'gender',
        'phone', 'valid_identity', 'passport'
    ];
    protected $hidden = [
        'user_id', "id"
    ];

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }
}

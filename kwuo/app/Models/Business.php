<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Business extends Model
{
    protected $fillable = [
        'user_id', 'business_name', 'category_id', 'logo', 'reference', "business_type",
        'address', 'status', 'website', 'phone', 'rc_number', 'email', 'cac',
    ];
    protected $hidden = [
        'user_id', 'reference', 'status', 'rc_number',
    ];

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function wallet()
    {
        return $this->hasOne(\App\Models\Wallet::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Wallet extends Model
{
    protected $fillable  = [
        'wallet', 'user_id', 'business_id', 'balance', 'reference', 'status'
    ];

    protected  $hidden = [
        "user_id", "business_id", 'updated_at', 'status', 'reference',
    ];
    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }
    public function business()
    {
        return $this->belongsTo(\App\Models\Business::class);
    }
    public function invoice()
    {
        return $this->belongsTo(\App\Models\Invoice::class);
    }
}

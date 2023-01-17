<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    protected $fillable = [
        'wallet_id', "amount", "type","recipient", "status", "reference",
        "is_visible", "message", "is_paid"
    ];

    public function wallet()
    {
        return $this->hasMany(\App\Modles\Wallet::class);
    }
}

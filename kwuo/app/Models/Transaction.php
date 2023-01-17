<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $fillable = [
         'wallet_id', 'status', 'reference', 'transaction_method', 'transaction_type', 'transfer_fee',
        'account_type','amount', 'previous_balance', 'new_balance','transaction_id', 'message', 'user_id', "network_provider"
     ];

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }
    protected $hidden = [
        'transaction_id', 'transaction_type', 'id', 'wallet_id', 'transaction_method',
    ];
}

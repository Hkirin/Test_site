<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CardDetail extends Model
{
    protected $fillable = [
        "user_id", "card_no", "card_holder_name", "card_cvv", "card_exp_date", "card_type",
        "reference", "type", "status", "mask_card", "transaction_id", "amount", "refund",
    ];
}

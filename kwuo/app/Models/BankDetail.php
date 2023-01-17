<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BankDetail extends Model
{
    protected $fillable = [
        "user_id", "account_no", "account_name", "bank_name", "account_type",
        "reference",  "status",
    ];
}

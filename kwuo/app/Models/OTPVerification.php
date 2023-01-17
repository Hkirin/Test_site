<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OTPVerification extends Model
{
    protected $fillable = ['otp', 'status', 'reference', 'type', 'exp_in', 'token', 'email', "phone"];
}

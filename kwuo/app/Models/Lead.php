<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class Lead extends Model
{
    use Notifiable;
    protected $fillable = ['firstname', 'lastname', 'email', 'phone', 'password'];
}

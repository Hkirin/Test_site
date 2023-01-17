<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\Wallet;
use Illuminate\Support\Str;
use Faker\Generator as Faker;

$factory->define(Wallet::class, function (Faker $faker) {
    return [
        'wallet' => substr(str_shuffle("ABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 2).random_int(10000, 99999),
        'reference' => Str::random(10),
    ];
});

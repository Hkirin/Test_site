<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\OTPVerification;
use Illuminate\Support\Str;
use Faker\Generator as Faker;

$factory->define(OTPVerification::class, function (Faker $faker) {
    return [
        'otp' => mt_rand(0000, 9999),
        'status' => mt_rand(0,1),
        'reference' => Str::random(60),
        'type' => null,
        'exp_in' => now()->addSeconds(80)
    ];
});

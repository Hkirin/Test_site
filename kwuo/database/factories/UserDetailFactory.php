<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\UserDetail;
use Faker\Generator as Faker;

$factory->define(UserDetail::class, function (Faker $faker) {
    return [
        'dob' => now()->format("y-m-d"),
        'address' => $faker->address,
        'country' => $faker->country,
        'phone' => $faker->phoneNumber,
        'state' => $faker->state,
        'city' => $faker->city,
        'lga' => $faker->text(10),
        'gender' => $faker->randomElement(['male', 'female']),
    ];
});

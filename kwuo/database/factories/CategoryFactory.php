<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\Category;
use Illuminate\Support\Str;
use Faker\Generator as Faker;

$factory->define(Category::class, function (Faker $faker) {
    return [
        "name" => $faker->text(10),
        "reference" => Str::random(8),
        "status" => mt_rand(0, 1),
    ];
});

<?php

use Faker\Generator as Faker;

$factory->define(App\Gallery::class, function (Faker $faker) {
    return [
        'name' => $faker->sentences(1, true),
        'description' => $faker->text(100)
    ];
});

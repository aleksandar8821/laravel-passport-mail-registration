<?php

use Faker\Generator as Faker;

$factory->define(App\ImageComment::class, function (Faker $faker) {
    return [
        'comment_body' => $faker->text(100),
        'user_id' => rand(1, 11)
    ];
});

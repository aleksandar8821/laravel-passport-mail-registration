<?php

use Faker\Generator as Faker;

/*
|--------------------------------------------------------------------------
| Model Factories
|--------------------------------------------------------------------------
|
| This directory should contain each of the model factory definitions for
| your application. Factories provide a convenient way to generate new
| model instances for testing / seeding your application's database.
|
*/

$factory->define(App\User::class, function (Faker $faker) {
    static $password; // kad ukucas static, on zadrzava vrednost prilikom vise poziva funkcije

// Ovakvo generisanje sifre, koje ne znam odakle sam skinuo, valjda od Miroslava jbg, kolko vidim, omogucuje da svi useri imaju istu sifru, tacnije isti hash, jer se i razliciti hashevi mogu odnositi na istu sifru (sto ce se i desiti ako dole uradis samo bcrypt('secret')), tako da ovo ne samo da omogucuje da svi useri imaju istu sifru nego isti hash u bazi. Jedino sto vidim cemu ovo pomaze je da laravel ne mora za svakog usera da pravi poseban hash, vec jednostavno ubaci svima isti i to je to... 
// Objasnjenje za ternary operator kod generisanja sifre: Since PHP 5.3, it is possible to leave out the middle part of the ternary operator. Expression expr1 ?: expr3 returns expr1 if expr1 evaluates to TRUE, and expr3 otherwise. (http://php.net/manual/en/language.operators.comparison.php)
    
    return [
        'first_name' => $faker->firstName,
        'last_name' => $faker->lastName,
        'email' => $faker->unique()->safeEmail,
        'password' => $password ?: $password = bcrypt('secret'),
        'remember_token' => str_random(10),
    ];
});

<?php

namespace Database\Factories;

use App\Models\Account;
use App\Support\Srp6;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Account> */
class AccountFactory extends Factory
{
    public function definition(): array
    {
        $username = strtoupper(fake()->unique()->lexify('player????????'));
        $salt = random_bytes(32);

        return [
            'username' => $username,
            'email' => fake()->unique()->safeEmail(),
            'salt' => $salt,
            'verifier' => Srp6::computeVerifier($username, 'password', $salt),
            'joindate' => now(),
        ];
    }
}

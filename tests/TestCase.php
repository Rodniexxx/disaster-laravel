<?php

namespace Tests;

use App\Models\Scenario;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Hash;

abstract class TestCase extends BaseTestCase
{
    /**
     * Create a user with the given attributes, defaulting to an active player.
     *
     * @param  array<string, mixed>  $attributes
     */
    protected function makeUser(array $attributes = []): User
    {
        $password = $attributes['password'] ?? 'password';
        unset($attributes['password']);

        return User::create(array_merge([
            'username' => 'player'.fake()->unique()->numerify('####'),
            'email' => fake()->unique()->safeEmail(),
            'password_hash' => Hash::make($password),
            'role' => User::ROLE_USER,
            'is_active' => true,
        ], $attributes));
    }

    protected function makeAdmin(array $attributes = []): User
    {
        return $this->makeUser(array_merge(['role' => User::ROLE_ADMIN], $attributes));
    }

    protected function makeScenario(array $attributes = []): Scenario
    {
        return Scenario::create(array_merge([
            'slug' => 'earthquake',
            'title' => 'Earthquake',
            'difficulty' => 'Medium',
            'status' => 'active',
        ], $attributes));
    }
}

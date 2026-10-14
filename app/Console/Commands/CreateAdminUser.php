<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

/**
 * Creates or promotes an administrator account. Prompts interactively for any
 * missing values so no credentials ever need to be hardcoded.
 */
class CreateAdminUser extends Command
{
    protected $signature = 'mangno:admin
        {--username= : The administrator username}
        {--email= : The administrator email address}
        {--password= : The administrator password (min 8 characters)}';

    protected $description = 'Create (or promote) a MANGNO administrator account';

    public function handle(): int
    {
        $username = $this->option('username') ?: $this->ask('Username');
        $email = $this->option('email') ?: $this->ask('Email address');
        $password = $this->option('password') ?: $this->secret('Password (min 8 characters)');

        $validator = Validator::make(
            compact('username', 'email', 'password'),
            [
                'username' => ['required', 'string', 'max:50'],
                'email' => ['required', 'string', 'email', 'max:100'],
                'password' => ['required', 'string', 'min:8'],
            ],
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $existing = User::query()
            ->where('username', $username)
            ->orWhere('email', $email)
            ->first();

        if ($existing) {
            $existing->forceFill([
                'username' => $username,
                'email' => $email,
                'password_hash' => Hash::make($password),
                'role' => User::ROLE_ADMIN,
                'is_active' => true,
            ])->save();

            $this->info("Promoted existing account '{$username}' to administrator.");

            return self::SUCCESS;
        }

        User::create([
            'username' => $username,
            'email' => $email,
            'password_hash' => Hash::make($password),
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
        ]);

        $this->info("Administrator '{$username}' created successfully.");

        return self::SUCCESS;
    }
}

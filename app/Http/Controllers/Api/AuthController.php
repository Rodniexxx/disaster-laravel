<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * Session-based authentication that mirrors the legacy api/login.php and
 * api/register.php endpoints the Unity client already talks to.
 */
class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $identifier = trim((string) (
            $request->input('username')
            ?? $request->input('email')
            ?? $request->input('login_identifier')
            ?? ''
        ));
        $password = (string) $request->input('password', '');

        if ($identifier === '' || $password === '') {
            return response()->json([
                'success' => false,
                'message' => 'Username/email and password are required.',
            ], 400);
        }

        try {
            $user = User::query()
                ->where('username', $identifier)
                ->orWhere('email', $identifier)
                ->first();

            if ($user && Hash::check($password, $user->password_hash) && $user->isActive()) {
                Auth::guard('web')->login($user);
                $request->session()->regenerate();

                $user->forceFill(['last_login_at' => now()])->save();

                return response()->json([
                    'success' => true,
                    'message' => 'Login successful',
                    'username' => $user->username,
                    'email' => $user->email,
                    'user_id' => (int) $user->id,
                    'role' => $user->role ?? User::ROLE_USER,
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Invalid username or password',
            ], 401);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Server error while logging in.',
            ], 500);
        }
    }

    public function register(Request $request): JsonResponse
    {
        $username = trim((string) $request->input('username', ''));
        $email = trim((string) $request->input('email', ''));
        $password = (string) $request->input('password', '');
        $confirm = (string) $request->input('confirm_password', '');

        if ($username === '' || $email === '' || $password === '') {
            return response()->json([
                'success' => false,
                'message' => 'Username, email, and password are required.',
            ], 400);
        }

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return response()->json([
                'success' => false,
                'message' => 'Please enter a valid email address.',
            ], 400);
        }

        if (strlen($password) < 6) {
            return response()->json([
                'success' => false,
                'message' => 'Password must be at least 6 characters long.',
            ], 400);
        }

        if ($confirm !== '' && $password !== $confirm) {
            return response()->json([
                'success' => false,
                'message' => 'Passwords do not match.',
            ], 400);
        }

        try {
            if (User::query()->where('username', $username)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Username is already taken.',
                ], 409);
            }

            if (User::query()->where('email', $email)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Email is already registered.',
                ], 409);
            }

            User::create([
                'username' => $username,
                'email' => $email,
                'password_hash' => Hash::make($password),
                'role' => User::ROLE_USER,
                'is_active' => true,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Registration successful',
                'username' => $username,
                'email' => $email,
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'The given data was invalid.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Server error while creating the account.',
            ], 500);
        }
    }
}

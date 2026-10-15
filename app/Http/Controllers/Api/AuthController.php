<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'username' => ['nullable', 'string', 'max:100'],
            'email' => ['nullable', 'string', 'max:100'],
            'login_identifier' => ['nullable', 'string', 'max:100'],
            'password' => ['required', 'string', 'max:255'],
            'device_name' => ['sometimes', 'string', 'max:100'],
        ]);
        $identifier = trim($request->input('username') ?: ($request->input('email') ?: $request->input('login_identifier', '')));
        if ($identifier === '') {
            return response()->json(['success' => false, 'message' => 'Username/email is required.'], 422);
        }
        $user = User::query()->where('username', $identifier)->orWhere('email', $identifier)->first();
        if (! $user || ! Hash::check($request->input('password'), $user->password_hash) || ! $user->isActive()) {
            return response()->json(['success' => false, 'message' => 'Invalid username or password'], 401);
        }
        return DB::transaction(function () use ($user, $request) {
            $user->forceFill(['last_login_at' => now()])->save();
            return $this->authenticated($user, $request, 'Login successful');
        });
    }

    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'username' => ['required', 'string', 'max:50'],
            'email' => ['required', 'email', 'max:100'],
            'password' => ['required', 'string', 'min:8', 'max:255'],
            'confirm_password' => ['sometimes', 'required', 'string', 'same:password'],
            'password_confirmation' => ['sometimes', 'required', 'string', 'same:password'],
            'device_name' => ['sometimes', 'string', 'max:100'],
        ]);
        if (User::query()->where('username', $data['username'])->exists() || User::query()->where('email', $data['email'])->exists()) {
            return response()->json(['success' => false, 'message' => 'Username or email is already registered.'], 409);
        }
        return DB::transaction(function () use ($data, $request) {
            $user = User::create([
                'username' => $data['username'],
                'email' => $data['email'],
                'password_hash' => Hash::make($data['password']),
                'role' => User::ROLE_USER,
                'is_active' => true,
            ]);
            return $this->authenticated($user, $request, 'Registration successful');
        });
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json(['success' => true, 'user' => $this->details($request->user())]);
    }

    public function logout(Request $request): JsonResponse
    {
        $token = $request->user()->currentAccessToken();
        if ($token instanceof PersonalAccessToken) {
            $token->delete();
        }
        return response()->json(['success' => true, 'message' => 'Logged out.']);
    }

    private function authenticated(User $user, Request $request, string $message): JsonResponse
    {
        $expires = now()->addDays(30);
        $token = $user->createToken($request->input('device_name', 'Unity Android'), ['scores:submit'], $expires);
        return response()->json(array_merge([
            'success' => true,
            'message' => $message,
            'token' => $token->plainTextToken,
            'token_type' => 'Bearer',
            'expires_at' => $expires->toIso8601String(),
        ], $this->details($user)));
    }

    private function details(User $user): array
    {
        return ['user_id' => (int) $user->id, 'username' => $user->username, 'email' => $user->email, 'role' => $user->role];
    }
}

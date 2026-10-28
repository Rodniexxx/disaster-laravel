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
            'email' => ['nullable', 'email', 'max:100'],
            'login_identifier' => ['nullable', 'string', 'max:100'],
            'password' => ['required', 'string', 'max:255'],
            'device_name' => ['sometimes', 'string', 'max:100'],
        ]);
        $email = trim((string) $request->input('email', ''));
        $identifier = trim((string) ($request->input('username') ?: $request->input('login_identifier', '')));
        if ($email === '' && $identifier === '') {
            return response()->json(['success' => false, 'message' => 'Email address is required.'], 422);
        }

        // New Unity clients submit an explicit email. Older clients may still submit
        // username or login_identifier, so retain that compatibility path.
        if ($email !== '') {
            $user = User::query()->whereRaw('LOWER(email) = ?', [strtolower($email)])->first();
        } else {
            $user = User::query()->where('username', $identifier)->first();
            if (! $user) {
                $user = User::query()->whereRaw('LOWER(email) = ?', [strtolower($identifier)])->first();
            }
        }
        if (! $user || ! Hash::check($request->input('password'), $user->password_hash) || ! $user->isActive()) {
            return response()->json(['success' => false, 'message' => 'Invalid email or password.'], 401);
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
        $data['email'] = strtolower(trim($data['email']));
        if (User::query()->where('username', $data['username'])->exists()
            || User::query()->whereRaw('LOWER(email) = ?', [$data['email']])->exists()) {
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
        return ['user_id' => (int) $user->id, 'username' => $user->username, 'email' => $user->email, 'role' => $user->role,
            'profile_picture_url' => $user->profile_picture_url];
    }
}

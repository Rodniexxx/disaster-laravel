<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AvatarService;
use App\Support\Username;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class RegisterController extends Controller
{
    public function create()
    {
        return view('auth.register');
    }

    public function store(Request $request, AvatarService $avatars)
    {
        $username = $request->input('username');
        if (is_string($username)) {
            $request->merge(['username' => Username::capitalizeWords($username)]);
        }

        $data = $request->validate([
            'username' => ['required', 'string', 'max:50', 'unique:users,username'],
            'email' => ['required', 'string', 'email', 'max:100', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
            'profile_picture' => $avatars->uploadRules(),
        ]);

        $attributes = [
            'username' => $data['username'],
            'email' => $data['email'],
            'password_hash' => Hash::make($data['password']),
            'role' => User::ROLE_USER,
            'is_active' => true,
        ];
        if ($request->hasFile('profile_picture')) {
            try { $avatars->register($attributes, $avatars->prepare($request->file('profile_picture'))); }
            catch (\Illuminate\Validation\ValidationException $error) { throw $error; }
            catch (\Throwable $error) {
                report($error);
                throw \Illuminate\Validation\ValidationException::withMessages(['profile_picture' => 'The picture could not be saved. Please retry, or register without a picture.']);
            }
        } else {
            User::create($attributes);
        }

        return redirect()->route('login')->with('status', 'Registration successful. Please log in.');
    }
}

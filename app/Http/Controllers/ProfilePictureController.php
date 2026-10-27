<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\AvatarService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ProfilePictureController extends Controller
{
    public function update(Request $request, AvatarService $avatars)
    {
        $data = $request->validate([
            'profile_picture' => $avatars->uploadRules(), 'remove_profile_picture' => ['sometimes', 'boolean'],
            'expected_revision' => ['required', 'integer', 'min:0'],
            'user_id' => ['prohibited'], 'avatar_path' => ['prohibited'], 'profile_picture_path' => ['prohibited'],
        ]);
        $remove = (bool) ($data['remove_profile_picture'] ?? false);
        if ($request->hasFile('profile_picture') && $remove) throw ValidationException::withMessages(['profile_picture' => 'Choose a replacement picture or removal, not both.']);
        if (! $request->hasFile('profile_picture') && ! $remove) return back()->with('status', 'No picture changes selected.');
        try {
            $bytes = $request->hasFile('profile_picture') ? $avatars->prepare($request->file('profile_picture')) : null;
            $avatars->save($request->user(), $bytes, $remove, expectedRevision: (int) $data['expected_revision']);
        } catch (ValidationException $error) { throw $error; }
        catch (\Throwable $error) {
            report($error);
            throw ValidationException::withMessages(['profile_picture' => 'The picture could not be saved. Your previous picture is unchanged. Reload and try again.']);
        }
        return back()->with('status', $remove ? 'Profile picture removed.' : 'Profile picture updated.');
    }

    /** Opaque, versioned images are public because they are shown on leaderboards. */
    public function show(User $user, string $version, AvatarService $avatars)
    {
        $profile = $user->playerProfile;
        abort_unless($user->isActive() && $profile?->avatar_version && hash_equals($profile->avatar_version, $version)
            && $avatars->owned((int) $user->id, $profile->avatar_path), 404);
        try { $bytes = $avatars->disk($profile->avatar_disk)->get($profile->avatar_path); }
        catch (\Throwable) { abort(503, 'Profile image could not be loaded.'); }
        abort_unless($bytes, 404);
        return response($bytes)->header('Content-Type', 'image/jpeg')->header('X-Content-Type-Options', 'nosniff')
            ->header('Content-Disposition', 'inline; filename="avatar.jpg"')->header('Cache-Control', 'public, max-age=300');
    }
}

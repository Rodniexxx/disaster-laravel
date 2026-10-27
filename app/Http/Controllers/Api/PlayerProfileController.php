<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PlayerProfile;
use App\Models\User;
use App\Services\AvatarService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PlayerProfileController extends Controller
{
    public function __construct(private readonly AvatarService $avatars) {}

    private function details(User $user, ?PlayerProfile $profile): array
    {
        return ['user_id' => (int) $user->id, 'username' => $user->username,
            'display_name' => $profile?->display_name ?? $user->username,
            'avatar_url' => $profile?->avatar_path ? '/api/profile/avatar?v='.$profile->avatar_version : null,
            'revision' => (int) ($profile?->revision ?? 0), 'avatar_upload_enabled' => $this->avatars->enabled(),
            'profile_picture_url' => $this->avatars->url((int) $user->id, $profile)];
    }

    public function show(Request $request)
    {
        return response()->json(['success' => true, 'profile' => $this->details($request->user(), PlayerProfile::find($request->user()->id))]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'display_name' => ['required', 'string', 'max:50', 'not_regex:/[\p{C}]/u'],
            'expected_revision' => ['required', 'integer', 'min:0'],
            'avatar_base64' => ['nullable', 'string', 'max:350000'],
            'remove_avatar' => ['sometimes', 'boolean'],
            'user_id' => ['prohibited'], 'username' => ['prohibited'], 'email' => ['prohibited'],
            'role' => ['prohibited'], 'avatar_url' => ['prohibited'], 'avatar_path' => ['prohibited'],
        ]);
        $data['display_name'] = trim($data['display_name']);
        if ($data['display_name'] === '') throw ValidationException::withMessages(['display_name' => ['Enter a display name.']]);
        $bytes = null;
        if (! empty($data['avatar_base64'])) {
            abort_unless($this->avatars->enabled(), 503, 'Photo uploads are disabled until persistent avatar storage is connected.');
            if (! empty($data['remove_avatar'])) throw ValidationException::withMessages(['avatar_base64' => ['Choose a replacement photo or removal, not both.']]);
            $raw = base64_decode($data['avatar_base64'], true);
            if ($raw === false) throw ValidationException::withMessages(['avatar_base64' => ['Use a PNG or JPEG image up to 256 KB and 1024 pixels per side.']]);
            $bytes = $this->avatars->encode($raw, 262144, 1024, [IMAGETYPE_PNG, IMAGETYPE_JPEG], 'avatar_base64');
        }
        try {
            $profile = $this->avatars->save($request->user(), $bytes, ! empty($data['remove_avatar']), $data['display_name'], (int) $data['expected_revision']);
        } catch (\Throwable $error) {
            if ($error instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface || $error instanceof ValidationException) throw $error;
            return response()->json(['success' => false, 'message' => 'Profile could not be saved. Your previous profile is unchanged. Please retry.'], 503);
        }
        return response()->json(['success' => true, 'profile' => $this->details($request->user(), $profile)]);
    }

    public function avatar(Request $request)
    {
        $profile = PlayerProfile::find($request->user()->id);
        abort_unless($profile?->avatar_path && $this->avatars->owned((int) $request->user()->id, $profile->avatar_path), 404);
        if ($request->query('v') && $request->query('v') !== $profile->avatar_version) abort(404);
        try { $bytes = $this->avatars->disk($profile->avatar_disk)->get($profile->avatar_path); }
        catch (\Throwable) { abort(503, 'Profile image could not be loaded.'); }
        abort_unless($bytes, 404);
        return response($bytes)->header('Content-Type', 'image/jpeg')->header('X-Content-Type-Options', 'nosniff')
            ->header('Cache-Control', 'private, no-store');
    }
}

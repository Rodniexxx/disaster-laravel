<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PlayerProfile;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PlayerProfileController extends Controller
{
    private function enabled(): bool
    {
        if (!config('profile.uploads_enabled')) return false;
        return match (config('profile.avatar_disk')) {
            's3' => (bool) config('filesystems.disks.s3.bucket'),
            'profile_volume' => str_starts_with((string) config('profile.volume_root'), '/')
                && !str_starts_with((string) config('profile.volume_root'), storage_path()),
            default => false,
        };
    }
    private function disk(string $name)
    {
        abort_unless(in_array($name, ['s3', 'profile_volume'], true), 503, 'Avatar storage is not configured.');
        if ($name === 's3') return Storage::disk('s3');
        abort_unless(str_starts_with((string) config('profile.volume_root'), '/'), 503, 'Avatar volume is not configured.');
        return Storage::build(['driver' => 'local', 'root' => config('profile.volume_root'), 'throw' => true]);
    }
    private function details(User $user, ?PlayerProfile $profile): array
    {
        return ['user_id' => (int) $user->id, 'username' => $user->username,
            'display_name' => $profile?->display_name ?? $user->username,
            'avatar_url' => $profile?->avatar_path ? '/api/profile/avatar?v='.$profile->avatar_version : null,
            'revision' => (int) ($profile?->revision ?? 0), 'avatar_upload_enabled' => $this->enabled()];
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
        if (!empty($data['avatar_base64'])) {
            abort_unless($this->enabled(), 503, 'Photo uploads are disabled until persistent avatar storage is connected.');
            if (!empty($data['remove_avatar'])) throw ValidationException::withMessages(['avatar_base64' => ['Choose a replacement photo or removal, not both.']]);
            $raw = base64_decode($data['avatar_base64'], true);
            $info = $raw === false ? false : @getimagesizefromstring($raw);
            if (!$info || strlen($raw) > 262144 || !in_array($info[2], [IMAGETYPE_PNG, IMAGETYPE_JPEG], true)
                || $info[0] > 1024 || $info[1] > 1024 || $info[0] < 1 || $info[1] < 1)
                throw ValidationException::withMessages(['avatar_base64' => ['Use a PNG or JPEG image up to 256 KB and 1024 pixels per side.']]);
            abort_unless(function_exists('imagecreatefromstring'), 503, 'Image processing is unavailable.');
            $source = @imagecreatefromstring($raw);
            if (!$source) throw ValidationException::withMessages(['avatar_base64' => ['The image could not be decoded.']]);
            $ratio = min(1, 512 / max($info[0], $info[1]));
            $image = imagecreatetruecolor(max(1, (int) round($info[0]*$ratio)), max(1, (int) round($info[1]*$ratio)));
            imagefill($image, 0, 0, imagecolorallocate($image, 255, 255, 255));
            imagecopyresampled($image, $source, 0, 0, 0, 0, imagesx($image), imagesy($image), $info[0], $info[1]);
            ob_start(); imagejpeg($image, null, 82); $bytes = ob_get_clean();
            imagedestroy($image); imagedestroy($source);
            if (strlen($bytes) > 262144) throw ValidationException::withMessages(['avatar_base64' => ['The compressed image is too large.']]);
        }
        $newPath = null; $oldPath = null; $oldDisk = null;
        try {
            $profile = DB::transaction(function () use ($request, $data, $bytes, &$newPath, &$oldPath, &$oldDisk) {
                $user = User::query()->whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
                $profile = PlayerProfile::find($user->id);
                abort_if((int) ($profile?->revision ?? 0) !== (int) $data['expected_revision'], 409, 'Your profile changed. Reload it before saving.');
                if (!$profile) { $profile = new PlayerProfile; $profile->user_id = $user->id; }
                $profile->display_name = $data['display_name'];
                if ($bytes !== null || !empty($data['remove_avatar'])) {
                    $oldPath = $profile->avatar_path; $oldDisk = $profile->avatar_disk;
                    $profile->avatar_path = null; $profile->avatar_disk = null; $profile->avatar_version = null;
                    if ($bytes !== null) {
                        $version = (string) Str::uuid(); $newPath = 'avatars/'.$user->id.'/'.$version.'.jpg';
                        if (!$this->disk(config('profile.avatar_disk'))->put($newPath, $bytes)) throw new \RuntimeException('Storage write failed.');
                        $profile->avatar_path = $newPath; $profile->avatar_disk = config('profile.avatar_disk'); $profile->avatar_version = $version;
                    }
                }
                $profile->revision = (int) $profile->revision + 1; $profile->save(); return $profile;
            });
        } catch (\Throwable $error) {
            if ($newPath) { try { $this->disk(config('profile.avatar_disk'))->delete($newPath); } catch (\Throwable) {} }
            if ($error instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface || $error instanceof ValidationException) throw $error;
            return response()->json(['success' => false, 'message' => 'Profile could not be saved. Your previous profile is unchanged. Please retry.'], 503);
        }
        if ($oldPath) { try { $this->disk($oldDisk)->delete($oldPath); } catch (\Throwable) { /* Old private file may be cleaned up later; the new profile is committed. */ } }
        return response()->json(['success' => true, 'profile' => $this->details($request->user(), $profile)]);
    }
    public function avatar(Request $request)
    {
        $profile = PlayerProfile::find($request->user()->id);
        abort_unless($profile?->avatar_path, 404);
        if ($request->query('v') && $request->query('v') !== $profile->avatar_version) abort(404);
        try { $bytes = $this->disk($profile->avatar_disk)->get($profile->avatar_path); }
        catch (\Throwable) { abort(503, 'Profile image could not be loaded.'); }
        abort_unless($bytes, 404);
        return response($bytes)->header('Content-Type', 'image/jpeg')->header('X-Content-Type-Options', 'nosniff')
            ->header('Cache-Control', 'private, no-store');
    }
}

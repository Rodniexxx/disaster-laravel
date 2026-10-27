<?php

namespace App\Services;

use App\Models\PlayerProfile;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AvatarService
{
    private function absolute(string $path): bool
    {
        return str_starts_with($path, '/') || preg_match('/^[A-Za-z]:[\\\\\/]/', $path) === 1;
    }

    public function enabled(): bool
    {
        if (! config('profile.uploads_enabled')) return false;
        if (config('profile.avatar_disk') === 's3') return (bool) config('filesystems.disks.s3.bucket');
        $root = (string) config('profile.volume_root');
        return config('profile.avatar_disk') === 'profile_volume' && $this->absolute($root)
            && (app()->environment('local') || ! str_starts_with(str_replace('\\', '/', $root), str_replace('\\', '/', base_path())));
    }

    public function disk(string $name)
    {
        abort_unless(in_array($name, ['s3', 'profile_volume'], true), 503, 'Avatar storage is not configured.');
        if ($name === 's3') return Storage::disk('s3');
        abort_unless($this->absolute((string) config('profile.volume_root')), 503, 'Avatar storage is not configured.');
        return Storage::build(['driver' => 'local', 'root' => config('profile.volume_root'), 'throw' => true]);
    }

    public function uploadRules(): array
    {
        return ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048', 'dimensions:max_width=4096,max_height=4096'];
    }

    public function prepare(UploadedFile $file): string
    {
        if (! $this->enabled()) throw ValidationException::withMessages(['profile_picture' => 'Photo uploads are temporarily unavailable. You can continue without a picture.']);
        return $this->encode($file->getContent(), 2097152, 4096, [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP], 'profile_picture');
    }

    /** Fully decode and re-encode, dropping embedded metadata and executable trailing data. */
    public function encode(string $raw, int $limit, int $side, array $types, string $field): string
    {
        $info = @getimagesizefromstring($raw);
        if (! $info || strlen($raw) > $limit || ! in_array($info[2], $types, true)
            || min($info[0], $info[1]) < 1 || max($info[0], $info[1]) > $side || $info[0] * $info[1] > 12000000) {
            throw ValidationException::withMessages([$field => 'Use a valid supported image within the size and dimension limits.']);
        }
        abort_unless(function_exists('imagecreatefromstring'), 503, 'Image processing is temporarily unavailable.');
        $source = @imagecreatefromstring($raw);
        if (! $source) throw ValidationException::withMessages([$field => 'The image could not be decoded.']);
        $ratio = min(1, 512 / max($info[0], $info[1]));
        $image = imagecreatetruecolor(max(1, (int) round($info[0] * $ratio)), max(1, (int) round($info[1] * $ratio)));
        imagefill($image, 0, 0, imagecolorallocate($image, 255, 255, 255));
        imagecopyresampled($image, $source, 0, 0, 0, 0, imagesx($image), imagesy($image), $info[0], $info[1]);
        ob_start(); imagejpeg($image, null, 82); $bytes = ob_get_clean();
        imagedestroy($image); imagedestroy($source);
        if (! is_string($bytes) || strlen($bytes) > $limit) throw ValidationException::withMessages([$field => 'The processed image is too large.']);
        return $bytes;
    }

    public function owned(int $userId, ?string $path): bool
    {
        return preg_match('~^avatars/'.$userId.'/[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}\.jpg$~iD', $path ?? '') === 1;
    }

    private function discard(int $userId, ?string $disk, ?string $path): void
    {
        if ($disk && $this->owned($userId, $path)) {
            try { $this->disk($disk)->delete($path); } catch (\Throwable) { /* Retry cleanup separately; never undo committed profile data. */ }
        }
    }

    public function save(User $user, ?string $bytes, bool $remove = false, ?string $displayName = null, ?int $expectedRevision = null): PlayerProfile
    {
        $newPath = $oldPath = $oldDisk = null;
        $newDisk = (string) config('profile.avatar_disk');
        try {
            $profile = DB::transaction(function () use ($user, $bytes, $remove, $displayName, $expectedRevision, $newDisk, &$newPath, &$oldPath, &$oldDisk) {
                User::whereKey($user->id)->lockForUpdate()->firstOrFail();
                $profile = PlayerProfile::find($user->id);
                abort_if($expectedRevision !== null && (int) ($profile?->revision ?? 0) !== $expectedRevision, 409, 'Your profile changed. Reload it before saving.');
                if (! $profile) { $profile = new PlayerProfile; $profile->user_id = $user->id; $profile->display_name = $user->username; }
                if ($displayName !== null) $profile->display_name = $displayName;
                if ($bytes !== null || $remove) {
                    $oldPath = $profile->avatar_path; $oldDisk = $profile->avatar_disk;
                    $profile->avatar_path = $profile->avatar_disk = $profile->avatar_version = null;
                    if ($bytes !== null) {
                        abort_unless($this->enabled(), 503, 'Avatar storage is not configured.');
                        $version = (string) Str::uuid(); $newPath = 'avatars/'.$user->id.'/'.$version.'.jpg';
                        if (! $this->disk($newDisk)->put($newPath, $bytes)) throw new \RuntimeException('Avatar storage write failed.');
                        $profile->avatar_path = $newPath; $profile->avatar_disk = $newDisk; $profile->avatar_version = $version;
                    }
                }
                $profile->revision = (int) $profile->revision + 1; $profile->save();
                return $profile;
            });
        } catch (\Throwable $error) {
            $this->discard((int) $user->id, $newDisk, $newPath);
            throw $error;
        }
        $this->discard((int) $user->id, $oldDisk, $oldPath);
        $user->unsetRelation('playerProfile');
        return $profile;
    }

    public function register(array $attributes, string $bytes): User
    {
        $user = null; $profile = null;
        try {
            return DB::transaction(function () use ($attributes, $bytes, &$user, &$profile) {
                $user = User::create($attributes);
                $profile = $this->save($user, $bytes);
                return $user;
            });
        } catch (\Throwable $error) {
            if ($user && $profile) $this->discard((int) $user->id, $profile->avatar_disk, $profile->avatar_path);
            throw $error;
        }
    }

    private function absoluteUrl(string $path): string
    {
        $base = config('profile.public_base_url');
        if (! $base && app()->environment('production')) {
            $base = (string) config('app.url');
            // Docker uses the production environment locally, without TLS.
            // Preserve its configured scheme and port; public hosts stay HTTPS.
            $host = strtolower((string) parse_url($base, PHP_URL_HOST));
            if (! in_array($host, ['localhost', '127.0.0.1', '[::1]', '::1'], true)) {
                $base = preg_replace('~^http://~i', 'https://', $base);
            }
        }
        if (is_string($base) && preg_match('~^https?://[^/?#]+(?:/[^?#]*)?$~', $base)) return rtrim($base, '/').'/'.ltrim($path, '/');
        return URL::to($path, [], app()->environment('production') ? true : null);
    }

    public function defaultUrl(): string { return $this->absoluteUrl('/images/default-avatar.png'); }

    public function url(int $userId, ?PlayerProfile $profile): string
    {
        return $profile?->avatar_version && $this->owned($userId, $profile->avatar_path)
            ? $this->absoluteUrl('/profile-pictures/'.$userId.'/'.$profile->avatar_version) : $this->defaultUrl();
    }

    public function forIds(array $ids): array
    {
        if ($ids === []) return [];
        return PlayerProfile::whereIn('user_id', $ids)->get()->mapWithKeys(fn ($profile) => [$profile->user_id => $this->url((int) $profile->user_id, $profile)])->all();
    }

    public function forNames(array $names): array
    {
        if ($names === []) return [];
        return User::whereIn('username', $names)->with('playerProfile')->get()->mapWithKeys(fn ($user) => [$user->username => $user->profile_picture_url])->all();
    }
}

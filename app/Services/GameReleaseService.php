<?php

namespace App\Services;

use App\Models\GameVersion;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Centralises how game builds are stored, located and served.
 *
 * Files live on the private "local" disk (storage/app/private/downloads/...),
 * which is outside the web root, and are always served through a controller
 * action. Stored file names are generated, so a user-supplied name can never
 * decide the on-disk path.
 */
class GameReleaseService
{
    public function disk(): string
    {
        return (string) config('mangno.downloads.disk', 'local');
    }

    /**
     * The release the public page should offer for a platform: the newest
     * published release that has a usable download, preferring one explicitly
     * flagged as latest.
     */
    public function latest(string $platform): ?GameVersion
    {
        return GameVersion::query()
            ->active()
            ->platform($platform)
            ->withDownload()
            ->orderByDesc('is_latest')
            ->orderByDesc('release_date')
            ->orderByDesc('id')
            ->get()
            ->first(fn (GameVersion $release) => $release->isAvailable());
    }

    /**
     * Store an uploaded build with a generated, safe file name.
     *
     * @return array{file_path:string,file_name:string,file_size:int}
     */
    public function storeUpload(UploadedFile $file, string $platform, string $version): array
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $base = trim(Str::slug($version), '-') ?: 'build';

        $name = sprintf(
            '%s-%s-%s-%s.%s',
            Str::lower($platform),
            $base,
            now()->format('Ymd'),
            Str::lower(Str::random(8)),
            $extension,
        );

        $directory = trim((string) config('mangno.downloads.path', 'downloads'), '/').'/'.Str::lower($platform);

        $path = $file->storeAs($directory, $name, $this->disk());

        return [
            'file_path' => $path,
            'file_name' => $this->safeName($file->getClientOriginalName()),
            'file_size' => (int) ($file->getSize() ?: 0),
        ];
    }

    /** Delete a stored build, ignoring a missing file. */
    public function deleteFile(?string $path): void
    {
        if ($path && Storage::disk($this->disk())->exists($path)) {
            Storage::disk($this->disk())->delete($path);
        }
    }

    /** Stream a stored build (streams from disk, never loads it into memory). */
    public function serve(GameVersion $release): BinaryFileResponse
    {
        abort_unless($release->hasLocalFile(), 404);

        return response()->download(
            Storage::disk($this->disk())->path($release->file_path),
            $this->safeName($release->file_name ?: basename($release->file_path)),
        );
    }

    /** Reduce an original client name to a safe display name. */
    private function safeName(string $name): string
    {
        $name = basename(str_replace('\\', '/', $name));

        return $name !== '' ? mb_substr($name, 0, 180) : 'MANGNO-download';
    }
}

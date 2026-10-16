<?php

namespace App\Http\Controllers;

use App\Services\GameReleaseService;
use App\Services\SettingsService;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Public download page and the file endpoints behind its buttons.
 *
 * All content comes from the database (game_versions) and the editable settings,
 * so administrators manage the page entirely from the admin area.
 */
class DownloadController extends Controller
{
    public function __construct(private readonly GameReleaseService $releases) {}

    public function __invoke(SettingsService $settings)
    {
        $content = $settings->all();

        return view('download', [
            'settings' => $content,
            'platforms' => [
                'windows' => $this->platform('windows', $content),
                'android' => $this->platform('android', $content),
            ],
        ]);
    }

    /** Latest published Windows build. */
    public function windows(): BinaryFileResponse
    {
        return $this->file('windows');
    }

    /** Latest published Android build. */
    public function android(): BinaryFileResponse
    {
        return $this->file('android');
    }

    /**
     * Stream the latest published build for a whitelisted platform.
     *
     * The platform is checked against configuration and the on-disk path comes
     * from the database, so no request value can ever reach the filesystem.
     */
    public function file(string $platform): BinaryFileResponse
    {
        $platform = strtolower($platform);

        abort_unless(array_key_exists($platform, (array) config('mangno.downloads.platforms', [])), 404);

        $release = $this->releases->latest($platform);

        abort_if($release === null || ! $release->hasLocalFile(), 404);

        return $this->releases->serve($release);
    }

    /**
     * Build the display payload for one platform card.
     *
     * @param  array<string, string>  $content
     * @return array<string, mixed>
     */
    private function platform(string $key, array $content): array
    {
        $definition = (array) config("mangno.downloads.platforms.{$key}", []);
        $release = $this->releases->latest($key);
        $available = $release !== null && $release->isAvailable();

        $extension = $release?->extension() ?: ltrim((string) ($definition['file_type'] ?? ''), '.');

        return [
            'key' => $key,
            'name' => $definition['name'] ?? ucfirst($key),
            'title' => ($release?->release_title ?: ($content["download_{$key}_title"] ?? null)) ?: ($definition['title'] ?? ucfirst($key)),
            'platform' => $definition['platform'] ?? ucfirst($key),
            'button' => $definition['button'] ?? 'Download',
            'description' => $content["download_{$key}_description"] ?? ($definition['description'] ?? ''),
            'file_type' => $extension !== '' ? '.'.$extension : null,
            'file_name' => $release?->file_name,
            'version' => $release?->version_number,
            'requirements' => $release?->requirements,
            'size' => $release?->formattedSize(),
            'release_date' => $release?->release_date,
            'release_notes' => $release?->release_notes,
            'status' => $release?->isPublished() ? 'Published' : 'Unpublished',
            'available' => $available,
            'url' => $available
                ? ($release->hasLocalFile() ? route('downloads.'.$key) : $release->download_url)
                : null,
        ];
    }
}

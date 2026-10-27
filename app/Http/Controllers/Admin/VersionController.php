<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GameVersion;
use App\Services\AuditLogger;
use App\Services\GameReleaseService;
use App\Services\SettingsService;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Admin "Game Versions" screen. This is the single place where the content of
 * the public /download page (text, Windows build, Android build) is managed.
 */
class VersionController extends Controller
{
    public function __construct(
        private readonly AuditLogger $audit,
        private readonly GameReleaseService $releases,
        private readonly SettingsService $settings,
    ) {}

    public function index(Request $request)
    {
        return view('admin.versions.index', [
            'versions' => GameVersion::query()
                ->orderByDesc('release_date')
                ->orderByDesc('created_at')
                ->get(),
            'editing' => $request->filled('edit')
                ? GameVersion::query()->find((int) $request->query('edit'))
                : null,
            'settings' => $this->settings->all(),
            'platforms' => (array) config('mangno.downloads.platforms', []),
            'prefillPlatform' => $request->query('platform'),
            'maxUploadMb' => $this->toMb((int) config('mangno.downloads.max_upload_kb', 2097152)),
            'serverUploadMb' => $this->toMb($this->serverUploadLimitKb()),
        ]);
    }

    /** Stream the uploaded build of a single release for admin review. */
    public function download(GameVersion $version): BinaryFileResponse
    {
        abort_unless($version->hasLocalFile(), 404);

        return $this->releases->serve($version);
    }

    public function store(Request $request)
    {
        $data = $this->validateRelease($request);
        $platform = $data['platform'];
        $file = $request->file('file');

        if ($file) {
            $data = array_merge($data, $this->releases->storeUpload($file, $platform, $data['version_number']));
        }

        $data['platform'] = $this->platformName($platform);

        $version = GameVersion::create($data);
        $this->syncLatest($version, $request->boolean('is_latest'));

        $this->audit->log($request->user(), 'created game version', 'game_versions', [
            'id' => $version->id,
            'version_number' => $version->version_number,
            'platform' => $version->platform,
        ]);

        return redirect()->route('admin.versions.index')->with('status', 'Release saved.');
    }

    public function update(Request $request, GameVersion $version)
    {
        $data = $this->validateRelease($request);
        $platform = $data['platform'];
        $file = $request->file('file');
        $previousFile = $version->file_path;

        if ($file) {
            $data = array_merge($data, $this->releases->storeUpload($file, $platform, $data['version_number']));
        }

        $data['platform'] = $this->platformName($platform);

        $version->update($data);
        $this->syncLatest($version, $request->boolean('is_latest'));

        // Only drop the old build once the replacement has been stored and saved.
        if ($file && $previousFile) {
            $this->releases->deleteFile($previousFile);
        }

        $this->audit->log($request->user(), 'updated game version', 'game_versions', [
            'id' => $version->id,
            'version_number' => $version->version_number,
        ]);

        return redirect()->route('admin.versions.index')->with('status', 'Release updated.');
    }

    public function destroy(Request $request, GameVersion $version)
    {
        $id = $version->id;
        $path = $version->file_path;

        $version->delete();
        $this->releases->deleteFile($path);

        $this->audit->log($request->user(), 'deleted game version', 'game_versions', ['id' => $id]);

        return redirect()->route('admin.versions.index')->with('status', 'Release deleted.');
    }

    public function toggle(Request $request, GameVersion $version)
    {
        $version->status = $version->isPublished()
            ? GameVersion::STATUS_UNPUBLISHED
            : GameVersion::STATUS_PUBLISHED;
        $version->save();

        $this->audit->log($request->user(), 'toggled game version', 'game_versions', [
            'id' => $version->id,
            'status' => $version->status,
        ]);

        $state = $version->isPublished() ? 'published' : 'unpublished';

        return redirect()->route('admin.versions.index')->with('status', "Release {$state}.");
    }

    public function setLatest(Request $request, GameVersion $version)
    {
        GameVersion::query()
            ->platform($version->platform)
            ->where('id', '!=', $version->id)
            ->update(['is_latest' => false]);

        $version->is_latest = true;
        $version->save();

        $this->audit->log($request->user(), 'marked game version latest', 'game_versions', ['id' => $version->id]);

        return redirect()->route('admin.versions.index')->with('status', 'Latest release updated.');
    }

    public function updateContent(Request $request)
    {
        $data = $request->validate([
            'download_section_eyebrow' => ['nullable', 'string', 'max:120'],
            'download_section_heading' => ['nullable', 'string', 'max:160'],
            'download_windows_title' => ['nullable', 'string', 'max:120'],
            'download_windows_description' => ['nullable', 'string', 'max:500'],
            'download_android_title' => ['nullable', 'string', 'max:120'],
            'download_android_description' => ['nullable', 'string', 'max:500'],
            'download_before_heading' => ['nullable', 'string', 'max:160'],
            'download_before_notes' => ['nullable', 'string', 'max:4000'],
            'download_about' => ['nullable', 'string', 'max:4000'],
            'download_extra_heading' => ['nullable', 'string', 'max:160'],
            'download_extra' => ['nullable', 'string', 'max:4000'],
        ]);

        $this->settings->put($data);

        $this->audit->log($request->user(), 'updated download content', 'settings', ['keys' => array_keys($data)]);

        return redirect()->route('admin.versions.index')->with('status', 'Download page content saved.');
    }

    /**
     * Validate a release submission and normalise it for persistence.
     *
     * @return array<string, mixed>
     */
    private function validateRelease(Request $request): array
    {
        $keys = array_keys((array) config('mangno.downloads.platforms', []));
        $platform = (string) $request->input('platform');

        $fileRules = ['nullable', 'file', 'max:'.(int) config('mangno.downloads.max_upload_kb', 2097152)];

        if (in_array($platform, $keys, true)) {
            $fileRules[] = $this->extensionRule($platform);
        }

        $data = $request->validate([
            'platform' => ['required', Rule::in($keys)],
            'version_number' => ['required', 'string', 'max:50'],
            'release_title' => ['nullable', 'string', 'max:150'],
            'release_notes' => ['nullable', 'string', 'max:5000'],
            'release_date' => ['nullable', 'date'],
            'requirements' => ['nullable', 'string', 'max:255'],
            'download_url' => ['nullable', 'url', 'max:255'],
            'status' => ['required', Rule::in([GameVersion::STATUS_PUBLISHED, GameVersion::STATUS_UNPUBLISHED])],
            'is_latest' => ['nullable', 'boolean'],
            'file' => $fileRules,
        ]);

        unset($data['file']);

        $data['version_number'] = trim($data['version_number']);
        $data['release_title'] = $data['release_title'] ?? null;
        $data['release_notes'] = $data['release_notes'] ?? null;
        $data['release_date'] = $data['release_date'] ?? null;
        $data['requirements'] = $data['requirements'] ?? null;
        $data['download_url'] = $data['download_url'] ?? null;
        $data['is_latest'] = $request->boolean('is_latest');

        return $data;
    }

    /** Server-side extension allow-list for the chosen platform. */
    private function extensionRule(string $platform): callable
    {
        $extensions = array_map('strtolower', (array) config("mangno.downloads.platforms.{$platform}.extensions", []));

        return function (string $attribute, mixed $value, callable $fail) use ($extensions): void {
            if (! $value instanceof UploadedFile) {
                return;
            }

            if (! in_array(strtolower($value->getClientOriginalExtension()), $extensions, true)) {
                $labels = implode(', ', array_map(fn (string $extension): string => '.'.$extension, $extensions));
                $fail("The build must be one of these file types: {$labels}.");
            }
        };
    }

    private function syncLatest(GameVersion $version, bool $isLatest): void
    {
        if (! $isLatest) {
            return;
        }

        GameVersion::query()
            ->platform($version->platform)
            ->where('id', '!=', $version->id)
            ->update(['is_latest' => false]);
    }

    private function platformName(string $key): string
    {
        return (string) (config("mangno.downloads.platforms.{$key}.name") ?? ucfirst($key));
    }

    private function toMb(int $kilobytes): int
    {
        return (int) max(1, (int) round($kilobytes / 1024));
    }

    private function serverUploadLimitKb(): int
    {
        $upload = $this->iniToKb((string) ini_get('upload_max_filesize'));
        $post = $this->iniToKb((string) ini_get('post_max_size'));

        $limits = array_filter([$upload, $post], fn (int $kb): bool => $kb > 0);

        return $limits === [] ? 0 : (int) min($limits);
    }

    private function iniToKb(string $value): int
    {
        $value = trim($value);

        if ($value === '') {
            return 0;
        }

        $number = (float) $value;

        return match (strtolower(substr($value, -1))) {
            'g' => (int) ($number * 1024 * 1024),
            'm' => (int) ($number * 1024),
            'k' => (int) $number,
            default => (int) ($number / 1024),
        };
    }
}

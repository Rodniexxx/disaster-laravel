<?php

namespace Tests\Feature;

use App\Models\GameVersion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DownloadPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Deterministic content and storage regardless of the developer's .env.
        Storage::fake('local');
    }

    /** @param array<string, mixed> $attributes */
    private function release(array $attributes = []): GameVersion
    {
        return GameVersion::create(array_merge([
            'version_number' => '1.0.0',
            'platform' => 'Windows',
            'status' => GameVersion::STATUS_PUBLISHED,
            'release_date' => now(),
        ], $attributes));
    }

    public function test_the_download_page_is_visible_to_guests(): void
    {
        $this->get('/download')
            ->assertOk()
            ->assertSee('Available Downloads')
            ->assertSee('Download for Windows')
            ->assertSee('Download for Android')
            ->assertSee('Before You Download')
            ->assertSee('Coming soon');
    }

    public function test_unpublished_releases_are_not_shown(): void
    {
        $this->release([
            'status' => GameVersion::STATUS_UNPUBLISHED,
            'version_number' => '9.9.9',
            'file_path' => 'downloads/windows/build.exe',
            'file_name' => 'MANGNO-Windows.exe',
        ]);

        $this->get('/download')
            ->assertOk()
            ->assertSee('Coming soon')
            ->assertDontSee('9.9.9')
            ->assertDontSee(route('downloads.windows'));
    }

    public function test_an_external_release_keeps_its_metadata_and_takes_priority_over_a_stored_file(): void
    {
        Storage::disk('local')->put('downloads/windows/build.exe', str_repeat('a', 2048));

        $this->release([
            'version_number' => '2.1.0',
            'release_title' => 'Storm Update',
            'file_path' => 'downloads/windows/build.exe',
            'file_name' => 'MANGNO-Windows.exe',
            'file_size' => 2048,
            'requirements' => 'Windows 10 (64-bit), 4 GB RAM',
            'download_url' => 'https://example.com/MANGNO.exe',
        ]);

        $this->get('/download')
            ->assertOk()
            ->assertSee('Storm Update')
            ->assertSee('2.1.0')
            ->assertSee('MANGNO-Windows.exe')
            ->assertSee('Windows 10 (64-bit), 4 GB RAM')
            ->assertSee('2 KB')
            ->assertSee('href="https://example.com/MANGNO.exe"', false);
    }

    public function test_an_external_url_release_is_shown_as_available(): void
    {
        $this->release([
            'version_number' => '3.0.0',
            'platform' => 'Android',
            'download_url' => 'https://example.com/MANGNO-Android.apk',
        ]);

        $this->get('/download')
            ->assertOk()
            ->assertSee('https://example.com/MANGNO-Android.apk')
            ->assertSee('href="https://example.com/MANGNO-Android.apk"', false);
    }

    public function test_platform_endpoints_redirect_to_the_correct_external_urls(): void
    {
        foreach (['windows' => 'exe', 'android' => 'apk'] as $platform => $extension) {
            $url = "https://example.com/MANGNO.{$extension}?download=1&platform={$platform}";
            $this->release(['platform' => ucfirst($platform), 'download_url' => $url]);

            $this->get("/download/{$platform}")->assertRedirect($url);
            $this->get("/download/{$platform}/file")->assertRedirect($url);
            $this->get('/download')->assertSee('href="'.e($url).'"', false);
        }
    }

    public function test_a_missing_build_returns_not_found(): void
    {
        $this->get('/download/windows')->assertNotFound();
        $this->get('/download/windows/file')->assertNotFound();
    }

    public function test_an_unknown_platform_is_not_found(): void
    {
        $this->get('/download/macos/file')->assertNotFound();
    }

    public function test_a_local_only_release_is_not_downloadable_even_when_its_file_exists(): void
    {
        Storage::disk('local')->put('downloads/windows/missing.exe', 'legacy build');
        $this->release([
            'file_path' => 'downloads/windows/missing.exe',
            'file_name' => 'missing.exe',
        ]);

        $this->get('/download/windows')->assertNotFound();
        Storage::disk('local')->assertExists('downloads/windows/missing.exe');

        $this->get('/download')
            ->assertOk()
            ->assertSee('Coming soon')
            ->assertDontSee(route('downloads.windows'));
    }

    public function test_latest_selection_skips_local_only_releases_and_unpublished_links(): void
    {
        $url = 'https://example.com/current.exe';
        $this->release(['download_url' => $url, 'release_date' => now()->subDays(2)]);
        $this->release(['file_path' => 'downloads/windows/new.exe', 'is_latest' => true]);
        $this->release([
            'download_url' => 'https://example.com/unpublished.exe',
            'status' => GameVersion::STATUS_UNPUBLISHED,
            'is_latest' => true,
        ]);

        $this->get('/download/windows')->assertRedirect($url);
        $this->get('/download')->assertSee('href="'.$url.'"', false);

        $preferred = 'https://example.com/preferred.exe';
        $this->release([
            'download_url' => $preferred,
            'release_date' => now()->subDays(3),
            'is_latest' => true,
        ]);
        $this->get('/download/windows')->assertRedirect($preferred);
    }

    public function test_empty_or_non_http_legacy_urls_are_not_downloadable(): void
    {
        foreach (['', 'ftp://example.com/build.exe', 'invalid'] as $url) {
            $version = $this->release(['download_url' => $url]);
            $this->get('/download/windows')->assertNotFound();
            $version->delete();
        }
    }
}

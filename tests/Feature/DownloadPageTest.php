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
        config()->set('mangno.downloads.max_upload_kb', 2097152);
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

    public function test_a_published_release_with_a_file_is_shown_with_its_metadata(): void
    {
        Storage::disk('local')->put('downloads/windows/build.exe', str_repeat('a', 2048));

        $this->release([
            'version_number' => '2.1.0',
            'release_title' => 'Storm Update',
            'file_path' => 'downloads/windows/build.exe',
            'file_name' => 'MANGNO-Windows.exe',
            'file_size' => 2048,
            'requirements' => 'Windows 10 (64-bit), 4 GB RAM',
        ]);

        $this->get('/download')
            ->assertOk()
            ->assertSee('Storm Update')
            ->assertSee('2.1.0')
            ->assertSee('MANGNO-Windows.exe')
            ->assertSee('Windows 10 (64-bit), 4 GB RAM')
            ->assertSee('2 KB')
            ->assertSee('href="'.route('downloads.windows').'"', false);
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

    public function test_the_latest_published_build_is_streamed_to_the_user(): void
    {
        Storage::disk('local')->put('downloads/windows/build.exe', 'fake-exe-bytes');

        $this->release([
            'file_path' => 'downloads/windows/build.exe',
            'file_name' => 'MANGNO-Windows.exe',
            'file_size' => 14,
        ]);

        $response = $this->get('/download/windows');

        $response->assertOk();
        $this->assertStringContainsString(
            'MANGNO-Windows.exe',
            (string) $response->headers->get('content-disposition')
        );
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

    public function test_a_published_release_with_a_missing_file_is_not_downloadable(): void
    {
        $this->release([
            'file_path' => 'downloads/windows/missing.exe',
            'file_name' => 'missing.exe',
        ]);

        $this->get('/download/windows')->assertNotFound();

        $this->get('/download')
            ->assertOk()
            ->assertSee('Coming soon')
            ->assertDontSee(route('downloads.windows'));
    }
}

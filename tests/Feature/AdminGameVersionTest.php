<?php

namespace Tests\Feature;

use App\Models\GameVersion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminGameVersionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        config()->set('mangno.downloads.max_upload_kb', 524288);
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

    /** @return array<string, mixed> */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'platform' => 'windows',
            'version_number' => '1.2.0',
            'release_title' => 'Storm Update',
            'status' => 'active',
            'release_notes' => 'Initial release.',
            'requirements' => 'Windows 10 (64-bit), 4 GB RAM',
        ], $overrides);
    }

    public function test_guests_and_players_cannot_manage_releases(): void
    {
        $this->get(route('admin.versions.index'))->assertRedirect(route('login'));
        $this->post(route('admin.versions.store'), $this->payload())->assertRedirect(route('login'));

        $player = $this->makeUser();
        $this->actingAs($player)->get(route('admin.versions.index'))->assertForbidden();
        $this->actingAs($player)->post(route('admin.versions.store'), $this->payload())->assertForbidden();
    }

    public function test_an_admin_can_open_the_version_management_page(): void
    {
        Storage::disk('local')->put('downloads/windows/build.exe', 'bytes');

        $this->release([
            'version_number' => '7.7.7',
            'release_title' => 'Table Row',
            'file_path' => 'downloads/windows/build.exe',
            'file_name' => 'MANGNO-Windows.exe',
            'file_size' => 5,
            'is_latest' => true,
        ]);

        // Legacy-style rows: one with no file, one with an external URL.
        $this->release(['version_number' => '0.9.0', 'platform' => 'Windows', 'is_latest' => false]);
        $this->release([
            'version_number' => '2.0.0',
            'platform' => 'Android',
            'download_url' => 'https://example.com/MANGNO.apk',
        ]);

        $this->actingAs($this->makeAdmin())
            ->get(route('admin.versions.index'))
            ->assertOk()
            ->assertSee('Game Version Management')
            ->assertSee('Download Page Content')
            ->assertSee('Windows Releases')
            ->assertSee('Android Releases')
            ->assertSee('7.7.7')
            ->assertSee('Table Row')
            ->assertSee('MANGNO-Windows.exe')
            ->assertSee('No file uploaded')
            ->assertSee('https://example.com/MANGNO.apk')
            ->assertSee('Latest')
            ->assertSee('Set Latest')
            ->assertSee('Unpublish')
            ->assertSee('Edit')
            ->assertSee('Delete');
    }

    public function test_an_admin_can_save_download_page_content(): void
    {
        $this->actingAs($this->makeAdmin())
            ->put(route('admin.versions.content'), [
                'download_heading' => 'Grab the Game',
                'download_intro' => 'A custom introduction.',
                'download_about' => '',
            ])
            ->assertRedirect(route('admin.versions.index'))
            ->assertSessionHasNoErrors();

        $this->get('/download')
            ->assertOk()
            ->assertSee('Grab the Game')
            ->assertSee('A custom introduction.')
            ->assertDontSee('About MANGNO');
    }

    public function test_an_admin_can_upload_a_windows_release(): void
    {
        $this->actingAs($this->makeAdmin())
            ->post(route('admin.versions.store'), $this->payload([
                'is_latest' => '1',
                'file' => UploadedFile::fake()->create('MANGNO-Windows.exe', 16, 'application/octet-stream'),
            ]))
            ->assertRedirect(route('admin.versions.index'))
            ->assertSessionHasNoErrors();

        $version = GameVersion::query()->firstOrFail();

        $this->assertSame('1.2.0', $version->version_number);
        $this->assertSame('Windows', $version->platform);
        $this->assertSame('MANGNO-Windows.exe', $version->file_name);
        $this->assertTrue($version->file_size > 0);
        $this->assertTrue($version->is_latest);
        Storage::disk('local')->assertExists($version->file_path);

        // The public page picks the new build up automatically.
        $this->get('/download')
            ->assertOk()
            ->assertSee('1.2.0')
            ->assertSee('Storm Update')
            ->assertSee('MANGNO-Windows.exe')
            ->assertSee(route('downloads.windows'));

        $this->get(route('downloads.windows'))->assertOk();
    }

    public function test_an_admin_can_upload_an_android_release(): void
    {
        $this->actingAs($this->makeAdmin())
            ->post(route('admin.versions.store'), $this->payload([
                'platform' => 'android',
                'version_number' => '4.5.1',
                'file' => UploadedFile::fake()->create('MANGNO-Android.apk', 32, 'application/vnd.android.package-archive'),
            ]))
            ->assertRedirect(route('admin.versions.index'))
            ->assertSessionHasNoErrors();

        $version = GameVersion::query()->firstOrFail();

        $this->assertSame('Android', $version->platform);
        Storage::disk('local')->assertExists($version->file_path);

        $this->get('/download')->assertOk()->assertSee('4.5.1');
        $this->get(route('downloads.android'))->assertOk();
    }

    public function test_invalid_file_types_are_rejected(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)
            ->post(route('admin.versions.store'), $this->payload([
                'file' => UploadedFile::fake()->create('payload.php', 2),
            ]))
            ->assertSessionHasErrors('file');

        $this->actingAs($admin)
            ->post(route('admin.versions.store'), $this->payload([
                'platform' => 'android',
                'file' => UploadedFile::fake()->create('build.exe', 2),
            ]))
            ->assertSessionHasErrors('file');

        $this->assertSame(0, GameVersion::query()->count());
    }

    public function test_unpublished_releases_are_not_public(): void
    {
        $this->actingAs($this->makeAdmin())
            ->post(route('admin.versions.store'), $this->payload([
                'version_number' => '5.0.0',
                'status' => 'inactive',
                'file' => UploadedFile::fake()->create('MANGNO-Windows.exe', 8),
            ]))
            ->assertRedirect();

        $this->get('/download')
            ->assertOk()
            ->assertSee('Coming soon')
            ->assertDontSee('5.0.0');
    }

    public function test_a_release_can_be_published_and_unpublished(): void
    {
        $admin = $this->makeAdmin();
        $version = $this->release(['status' => GameVersion::STATUS_UNPUBLISHED]);

        $this->actingAs($admin)
            ->patch(route('admin.versions.toggle', $version))
            ->assertRedirect();

        $this->assertSame(GameVersion::STATUS_PUBLISHED, $version->refresh()->status);

        $this->actingAs($admin)
            ->patch(route('admin.versions.toggle', $version))
            ->assertRedirect();

        $this->assertSame(GameVersion::STATUS_UNPUBLISHED, $version->refresh()->status);
    }

    public function test_marking_a_release_latest_unsets_the_others(): void
    {
        $older = $this->release(['version_number' => '1.0.0', 'is_latest' => true]);
        $newer = $this->release(['version_number' => '2.0.0', 'is_latest' => false]);

        $this->actingAs($this->makeAdmin())
            ->patch(route('admin.versions.latest', $newer))
            ->assertRedirect();

        $this->assertTrue($newer->refresh()->is_latest);
        $this->assertFalse($older->refresh()->is_latest);
    }

    public function test_replacing_a_build_removes_the_old_file(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)->post(route('admin.versions.store'), $this->payload([
            'file' => UploadedFile::fake()->create('MANGNO-Windows.exe', 8),
        ]))->assertRedirect();

        $version = GameVersion::query()->firstOrFail();
        $oldPath = $version->file_path;

        $this->actingAs($admin)->put(route('admin.versions.update', $version), $this->payload([
            'version_number' => '1.2.1',
            'file' => UploadedFile::fake()->create('MANGNO-Windows-new.exe', 12),
        ]))->assertRedirect()->assertSessionHasNoErrors();

        $version->refresh();

        $this->assertNotSame($oldPath, $version->file_path);
        $this->assertSame('MANGNO-Windows-new.exe', $version->file_name);
        Storage::disk('local')->assertMissing($oldPath);
        Storage::disk('local')->assertExists($version->file_path);
    }

    public function test_an_admin_can_delete_a_release_and_its_file(): void
    {
        Storage::disk('local')->put('downloads/windows/old.exe', 'bytes');
        $version = $this->release([
            'file_path' => 'downloads/windows/old.exe',
            'file_name' => 'old.exe',
            'file_size' => 5,
        ]);

        $this->actingAs($this->makeAdmin())
            ->delete(route('admin.versions.destroy', $version))
            ->assertRedirect();

        $this->assertDatabaseMissing('game_versions', ['id' => $version->id]);
        Storage::disk('local')->assertMissing('downloads/windows/old.exe');
    }

    public function test_an_admin_can_download_a_specific_release_file(): void
    {
        Storage::disk('local')->put('downloads/windows/x.exe', 'bytes');
        $version = $this->release([
            'file_path' => 'downloads/windows/x.exe',
            'file_name' => 'MANGNO-Windows.exe',
            'file_size' => 5,
        ]);

        $response = $this->actingAs($this->makeAdmin())
            ->get(route('admin.versions.download', $version));

        $response->assertOk();
        $this->assertStringContainsString(
            'MANGNO-Windows.exe',
            (string) $response->headers->get('content-disposition')
        );
    }
}

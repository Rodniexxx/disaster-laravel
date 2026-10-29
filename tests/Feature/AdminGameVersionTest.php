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
            'download_url' => 'https://example.com/MANGNO.exe',
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
            ->assertDontSee('type="file"', false)
            ->assertDontSee('multipart/form-data')
            ->assertDontSee('Build File')
            ->assertSee('No external URL set')
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
                'download_section_heading' => 'Choose your MANGNO download',
                'download_windows_description' => 'A custom Windows description.',
                'download_about' => '',
            ])
            ->assertRedirect(route('admin.versions.index'))
            ->assertSessionHasNoErrors();

        $this->get('/download')
            ->assertOk()
            ->assertSee('Choose your MANGNO download')
            ->assertSee('A custom Windows description.')
            ->assertDontSee('id="about"', false);
    }

    public function test_unpublished_releases_are_not_public(): void
    {
        $this->actingAs($this->makeAdmin())
            ->post(route('admin.versions.store'), $this->payload([
                'version_number' => '5.0.0',
                'status' => 'inactive',
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

    public function test_deleting_a_release_preserves_its_existing_file(): void
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
        Storage::disk('local')->assertExists('downloads/windows/old.exe');
    }

    public function test_an_admin_can_create_and_edit_external_links_for_both_platforms(): void
    {
        $this->actingAs($this->makeAdmin());

        foreach (['windows' => 'exe', 'android' => 'apk'] as $platform => $extension) {
            $url = "https://example.com/MANGNO.{$extension}";
            $this->post(route('admin.versions.store'), $this->payload([
                'platform' => $platform,
                'download_url' => $url,
                'is_latest' => true,
            ]))->assertRedirect(route('admin.versions.index'))->assertSessionHasNoErrors();

            $version = GameVersion::query()->platform($platform)->firstOrFail();
            $this->assertSame($url, $version->download_url);
            $this->assertNull($version->file_path);
            $this->get("/download/{$platform}")->assertRedirect($url);

            $updatedUrl = $url.'?release=2';
            $this->put(route('admin.versions.update', $version), $this->payload([
                'platform' => $platform,
                'download_url' => $updatedUrl,
            ]))->assertRedirect(route('admin.versions.index'))->assertSessionHasNoErrors();

            $this->assertSame($updatedUrl, $version->refresh()->download_url);
            $this->get("/download/{$platform}")->assertRedirect($updatedUrl);
        }

        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_installer_uploads_are_rejected_on_create_and_update(): void
    {
        $this->actingAs($this->makeAdmin());

        foreach (['windows' => 'exe', 'android' => 'apk'] as $platform => $extension) {
            $payload = $this->payload([
                'platform' => $platform,
                'file' => UploadedFile::fake()->create('build.'.$extension, 8),
            ]);
            $this->post(route('admin.versions.store'), $payload)->assertSessionHasErrors('file');
            $this->assertDatabaseCount('game_versions', 0);

            $version = $this->release(['platform' => ucfirst($platform)]);
            $this->put(route('admin.versions.update', $version), $payload)->assertSessionHasErrors('file');
            $this->assertSame('1.0.0', $version->refresh()->version_number);
            $this->assertNull($version->file_path);
            $version->delete();
        }

        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_editing_a_legacy_release_updates_metadata_and_preserves_its_file(): void
    {
        $path = 'downloads/windows/old.exe';
        Storage::disk('local')->put($path, 'bytes');
        $version = $this->release([
            'file_path' => $path,
            'file_name' => 'old.exe',
            'file_size' => 5,
        ]);

        $this->actingAs($this->makeAdmin())
            ->put(route('admin.versions.update', $version), $this->payload([
                'file_path' => 'downloads/replacement.exe',
                'file_name' => 'replacement.exe',
                'file_size' => 100,
            ]))->assertRedirect()->assertSessionHasNoErrors();

        $this->assertSame($path, $version->refresh()->file_path);
        $this->assertSame('replacement.exe', $version->file_name);
        $this->assertSame(100, $version->file_size);
        Storage::disk('local')->assertExists($path);
        $this->get('/download/windows')->assertRedirect('https://example.com/MANGNO.exe');
    }

    public function test_file_metadata_can_be_created_prefilled_displayed_and_cleared(): void
    {
        $this->actingAs($this->makeAdmin());

        foreach (['windows' => 'exe', 'android' => 'apk'] as $platform => $extension) {
            $name = 'MANGNO-build.'.$extension;
            $this->post(route('admin.versions.store'), $this->payload([
                'platform' => $platform, 'file_name' => $name, 'file_size' => 104857600,
            ]))->assertRedirect()->assertSessionHasNoErrors();

            $version = GameVersion::query()->platform($platform)->firstOrFail();
            $this->assertSame($name, $version->file_name);
            $this->assertSame(104857600, $version->file_size);
            $this->get(route('admin.versions.index', ['edit' => $version->id]))
                ->assertOk()->assertSee('name="file_name"', false)
                ->assertSee('value="'.$name.'"', false)->assertSee('value="104857600"', false);
            $this->get('/download')->assertOk()->assertSee($name)->assertSee('100 MB');

            $this->put(route('admin.versions.update', $version), $this->payload([
                'platform' => $platform, 'file_name' => '', 'file_size' => '',
            ]))->assertRedirect()->assertSessionHasNoErrors();
            $this->assertNull($version->refresh()->file_name);
            $this->assertNull($version->file_size);
        }
    }

    public function test_invalid_file_metadata_is_rejected(): void
    {
        $this->actingAs($this->makeAdmin());
        foreach ([-1, 1.5, '100 MB'] as $size) {
            $this->post(route('admin.versions.store'), $this->payload(['file_size' => $size]))
                ->assertSessionHasErrors('file_size');
        }
        $this->post(route('admin.versions.store'), $this->payload(['file_name' => str_repeat('a', 256)]))
            ->assertSessionHasErrors('file_name');
        $this->assertDatabaseCount('game_versions', 0);
    }

    public function test_the_admin_stored_file_endpoint_is_removed(): void
    {
        $version = $this->release();
        $this->actingAs($this->makeAdmin())
            ->get("/admin/versions/{$version->id}/file")->assertNotFound();
    }

    public function test_invalid_or_non_http_download_urls_are_rejected(): void
    {
        $this->actingAs($this->makeAdmin());

        foreach (['invalid', 'ftp://example.com/build.exe', 'javascript:alert(1)'] as $url) {
            $this->post(route('admin.versions.store'), $this->payload([
                'download_url' => $url,
            ]))->assertSessionHasErrors('download_url');
        }

        $this->assertDatabaseCount('game_versions', 0);
    }
}

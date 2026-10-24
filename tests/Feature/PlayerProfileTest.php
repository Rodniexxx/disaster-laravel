<?php

namespace Tests\Feature;

use App\Models\PlayerProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PlayerProfileTest extends TestCase
{
    use RefreshDatabase;
    private function player(): User
    {
        static $number = 0; $number++;
        return User::create(['username' => 'profile_test_'.$number, 'email' => 'profile'.$number.'@example.test',
            'password_hash' => bcrypt('test-password'), 'role' => 'user', 'is_active' => true]);
    }
    private function image(): string { return base64_encode(UploadedFile::fake()->image('avatar.png', 100, 80)->getContent()); }
    private function storage(): void
    {
        Storage::fake('s3'); config(['profile.uploads_enabled' => true, 'profile.avatar_disk' => 's3', 'filesystems.disks.s3.bucket' => 'test-avatars']);
    }
    public function test_authentication_and_private_details(): void
    {
        $this->getJson('/api/profile')->assertUnauthorized(); $this->postJson('/api/profile', [])->assertUnauthorized();
        $this->getJson('/api/profile/avatar')->assertUnauthorized();
        $user = $this->player(); Sanctum::actingAs($user);
        $response = $this->getJson('/api/profile')->assertOk()->assertJsonPath('profile.username', $user->username);
        $this->assertArrayNotHasKey('email', $response->json('profile'));
        $this->assertArrayNotHasKey('password_hash', $response->json('profile'));
    }
    public function test_display_name_persists_and_account_fields_are_immutable(): void
    {
        $user = $this->player(); Sanctum::actingAs($user);
        $this->postJson('/api/profile', ['display_name' => 'Prepared Citizen', 'expected_revision' => 0])->assertOk()->assertJsonPath('profile.revision', 1);
        $this->getJson('/api/profile')->assertJsonPath('profile.display_name', 'Prepared Citizen');
        $this->postJson('/api/profile', ['display_name' => 'Name', 'expected_revision' => 1, 'username' => 'rename', 'user_id' => 999])->assertUnprocessable();
        $this->assertSame($user->username, $user->fresh()->username);
        $other = $this->player(); Sanctum::actingAs($other);
        $this->getJson('/api/profile')->assertJsonPath('profile.display_name', $other->username);
        Sanctum::actingAs($user);
        $this->getJson('/api/profile')->assertJsonPath('profile.display_name', 'Prepared Citizen');
        $this->assertDatabaseCount('leaderboard', 0); $this->assertDatabaseCount('readiness_progress', 0);
    }
    public function test_uploads_fail_closed_until_durable_storage_is_configured(): void
    {
        config(['profile.uploads_enabled' => false]); Sanctum::actingAs($this->player());
        $this->getJson('/api/profile')->assertJsonPath('profile.avatar_upload_enabled', false);
        $this->postJson('/api/profile', ['display_name' => 'Name', 'expected_revision' => 0, 'avatar_base64' => $this->image()])->assertStatus(503);
        $this->assertDatabaseCount('player_profiles', 0);
        config(['profile.uploads_enabled' => true, 'profile.avatar_disk' => 'public']);
        $this->getJson('/api/profile')->assertJsonPath('profile.avatar_upload_enabled', false);
    }
    public function test_photo_replace_remove_and_relogin_persistence(): void
    {
        $this->storage(); $user = $this->player(); Sanctum::actingAs($user);
        $first = $this->postJson('/api/profile', ['display_name' => 'Name', 'expected_revision' => 0, 'avatar_base64' => $this->image()])->assertOk();
        $path = PlayerProfile::find($user->id)->avatar_path; Storage::disk('s3')->assertExists($path);
        $this->get('/api/profile/avatar')->assertOk()->assertHeader('Content-Type', 'image/jpeg')->assertHeader('X-Content-Type-Options', 'nosniff');
        $other = $this->player(); Sanctum::actingAs($other); $this->get('/api/profile/avatar')->assertNotFound();
        Sanctum::actingAs($user); $this->getJson('/api/profile')->assertJsonPath('profile.avatar_url', $first->json('profile.avatar_url'));
        $this->postJson('/api/profile', ['display_name' => 'Name', 'expected_revision' => 1, 'avatar_base64' => $this->image()])->assertOk();
        Storage::disk('s3')->assertMissing($path);
        $replacement = PlayerProfile::find($user->id)->avatar_path;
        $this->postJson('/api/profile', ['display_name' => 'Name', 'expected_revision' => 2, 'remove_avatar' => true])->assertOk()->assertJsonPath('profile.avatar_url', null);
        Storage::disk('s3')->assertMissing($replacement); $this->get('/api/profile/avatar')->assertNotFound();
    }
    public function test_invalid_photos_and_stale_edits_do_not_change_the_profile(): void
    {
        $this->storage(); Sanctum::actingAs($this->player());
        foreach (['not-base64', base64_encode('<svg></svg>'), str_repeat('A', 350001), base64_encode(UploadedFile::fake()->image('big.png', 1200, 2)->getContent())] as $bad)
            $this->postJson('/api/profile', ['display_name' => 'Name', 'expected_revision' => 0, 'avatar_base64' => $bad])->assertUnprocessable();
        $this->postJson('/api/profile', ['display_name' => 'First', 'expected_revision' => 0])->assertOk();
        $this->postJson('/api/profile', ['display_name' => 'Stale', 'expected_revision' => 0])->assertStatus(409);
        $this->getJson('/api/profile')->assertJsonPath('profile.display_name', 'First');
    }
    public function test_storage_failure_preserves_saved_profile(): void
    {
        $this->storage(); $user = $this->player(); Sanctum::actingAs($user);
        $this->postJson('/api/profile', ['display_name' => 'Original', 'expected_revision' => 0])->assertOk();
        $disk = \Mockery::mock(); $disk->shouldReceive('put')->once()->andThrow(new \RuntimeException('offline'));
        $disk->shouldReceive('delete')->once()->andReturn(true); Storage::shouldReceive('disk')->with('s3')->andReturn($disk);
        $this->postJson('/api/profile', ['display_name' => 'Unsaved', 'expected_revision' => 1, 'avatar_base64' => $this->image()])->assertStatus(503);
        $this->assertSame('Original', PlayerProfile::find($user->id)->display_name);
        $this->assertNull(PlayerProfile::find($user->id)->avatar_path);
    }
}

<?php

namespace Tests\Feature;

use App\Models\PlayerProfile;
use App\Services\ReadinessService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PublicLeaderboardTest extends TestCase
{
    use RefreshDatabase;

    private function complete($user, string $scenario, int $score): void
    {
        DB::table('readiness_progress')->insert(['user_id' => $user->id, 'scenario' => $scenario, 'completed' => true,
            'best_overall_score' => $score, 'best_gameplay_score' => $score, 'best_quiz_score' => $score, 'stars' => 1]);
    }
    private function reference(): string
    {
        return $this->getJson('/api/readiness/leaderboard/view')->assertOk()->json('leaderboard.0.profile_ref');
    }

    public function test_authentication_validation_and_read_only_routes(): void
    {
        foreach (['readiness/leaderboard/view', 'public-profile', 'public-profile/avatar'] as $path)
            $this->getJson('/api/'.$path)->assertUnauthorized();
        $user = $this->makeUser(); Sanctum::actingAs($user);
        $this->getJson('/api/readiness/leaderboard/view?category=Flood')->assertUnprocessable();
        $this->getJson('/api/public-profile')->assertUnprocessable();
        $this->getJson('/api/public-profile?profile_ref=123')->assertNotFound();
        foreach (['postJson', 'putJson', 'deleteJson'] as $method) $this->$method('/api/public-profile', [])->assertStatus(405);
        $user->is_active = false; $user->save();
        $this->getJson('/api/readiness/leaderboard/view')->assertForbidden();
    }

    public function test_exact_top_100_order_and_actual_rank_101_in_every_category(): void
    {
        $players = [];
        for ($i = 0; $i < 101; $i++) {
            $user = $this->makeUser(); $players[] = $user;
            foreach (array_keys(ReadinessService::CRITERIA) as $scenario) $this->complete($user, $scenario, 80);
        }
        $inactive = $this->makeUser(['is_active' => false]); $this->complete($inactive, 'Fire', 100);
        $last = $players[100]; Sanctum::actingAs($last);
        foreach (['Overall', ...array_keys(ReadinessService::CRITERIA)] as $category) {
            $original = app(ReadinessService::class)->leaderboard($category);
            $response = $this->getJson('/api/readiness/leaderboard/view?category='.$category)->assertOk()
                ->assertJsonCount(100, 'leaderboard')->assertJsonPath('current_player.rank', 101)
                ->assertJsonPath('current_player.is_current_player', true);
            $publicRows = array_map(fn ($row) => array_intersect_key($row, array_flip(['rank', 'username', 'score', 'category'])), $response->json('leaderboard'));
            $this->assertSame($original, $publicRows);
            $this->assertNotContains(true, array_column($response->json('leaderboard'), 'is_current_player'));
        }
        Sanctum::actingAs($players[0]);
        $this->getJson('/api/readiness/leaderboard/view')->assertJsonPath('leaderboard.0.is_current_player', true)
            ->assertJsonPath('current_player.rank', 1)->assertJsonPath('current_player.score', 320);
        $this->assertDatabaseCount('readiness_progress', 405);
    }

    public function test_unranked_and_category_specific_rank(): void
    {
        $first = $this->makeUser(); $second = $this->makeUser();
        $this->complete($first, 'Fire', 100); $this->complete($second, 'Earthquake', 90);
        Sanctum::actingAs($second);
        $this->getJson('/api/readiness/leaderboard/view')->assertJsonPath('current_player.rank', 2);
        $this->getJson('/api/readiness/leaderboard/view?category=Earthquake')->assertJsonPath('current_player.rank', 1);
        $this->getJson('/api/readiness/leaderboard/view?category=Fire')->assertJsonPath('current_player', null);
    }

    public function test_public_projection_has_no_private_fields_and_does_not_write(): void
    {
        $target = $this->makeUser(); $viewer = $this->makeUser(); $this->complete($target, 'Fire', 82);
        PlayerProfile::create(['user_id' => $target->id, 'display_name' => 'Prepared Citizen', 'revision' => 1]);
        DB::table('readiness_achievements')->insert(['user_id' => $target->id, 'achievement_id' => 'ready_to_respond', 'unlocked_at' => now()]);
        Sanctum::actingAs($viewer);
        $reference = $this->reference();
        $profile = $this->getJson('/api/public-profile?'.http_build_query(['profile_ref' => $reference]))->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private')->assertJsonPath('profile.display_name', 'Prepared Citizen')
            ->assertJsonPath('profile.overall_rank', 1)->assertJsonPath('profile.overall_score', 82)
            ->assertJsonPath('profile.completed_scenarios', ['Fire'])->assertJsonPath('profile.achievements', ['ready_to_respond'])
            ->assertJsonPath('profile.has_avatar', false)->json('profile');
        $this->assertEqualsCanonicalizing(['display_name', 'has_avatar', 'overall_rank', 'overall_score', 'completed_scenarios', 'achievements'], array_keys($profile));
        $row = $this->getJson('/api/readiness/leaderboard/view')->json('leaderboard.0');
        $this->assertEqualsCanonicalizing(['rank', 'username', 'score', 'category', 'is_current_player', 'profile_ref'], array_keys($row));
        $this->assertDatabaseCount('readiness_progress', 1); $this->assertDatabaseCount('readiness_achievements', 1);
        $this->assertDatabaseCount('readiness_attempts', 0); $this->assertDatabaseCount('player_profiles', 1);
        $this->get('/api/public-profile/avatar?'.http_build_query(['profile_ref' => $reference]))->assertNotFound();
    }

    public function test_no_badges_locator_tampering_expiry_and_account_switch(): void
    {
        $target = $this->makeUser(); $viewer = $this->makeUser(); $this->complete($target, 'Typhoon', 0);
        Sanctum::actingAs($viewer); $reference = $this->reference();
        $path = '/api/public-profile?'.http_build_query(['profile_ref' => $reference]);
        $this->getJson($path)->assertOk()->assertJsonPath('profile.achievements', [])->assertJsonPath('profile.overall_score', 0);
        $this->getJson('/api/public-profile?'.http_build_query(['profile_ref' => $reference.'x']))->assertNotFound();
        Sanctum::actingAs($target); $this->getJson($path)->assertNotFound();
        Sanctum::actingAs($viewer); $this->travel(16)->minutes(); $this->getJson($path)->assertNotFound(); $this->travelBack();
        $reference = $this->reference(); $target->is_active = false; $target->save();
        $this->getJson('/api/public-profile?'.http_build_query(['profile_ref' => $reference]))->assertNotFound();
    }

    public function test_avatar_uses_existing_private_storage_and_never_returns_path(): void
    {
        Storage::fake('s3'); $target = $this->makeUser(); $this->complete($target, 'Fire', 80);
        $bytes = \Illuminate\Http\UploadedFile::fake()->image('avatar.jpg', 64, 64)->getContent();
        Storage::disk('s3')->put('avatars/private.jpg', $bytes);
        PlayerProfile::create(['user_id' => $target->id, 'display_name' => 'Player', 'avatar_disk' => 's3', 'avatar_path' => 'avatars/private.jpg', 'revision' => 1]);
        Sanctum::actingAs($this->makeUser()); $reference = $this->reference();
        $this->getJson('/api/public-profile?'.http_build_query(['profile_ref' => $reference]))->assertJsonPath('profile.has_avatar', true);
        $response = $this->get('/api/public-profile/avatar?'.http_build_query(['profile_ref' => $reference]))->assertOk()
            ->assertHeader('Content-Type', 'image/jpeg')->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->assertSame($bytes, $response->getContent());
    }
}

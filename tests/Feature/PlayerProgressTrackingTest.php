<?php

namespace Tests\Feature;

use App\Models\PlayerProgress;
use App\Models\User;
use App\Services\PlayerProgressService;
use App\Services\ReadinessService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PlayerProgressTrackingTest extends TestCase
{
    use RefreshDatabase;

    private function summary(User $user): array
    {
        $service = app(PlayerProgressService::class);
        return $service->describe($service->players(userId: $user->id)->first());
    }

    private function attempt(string $scenario, bool $completed = true): array
    {
        return ['attempt_id' => (string) Str::uuid(), 'scenario' => $scenario, 'completed' => $completed,
            'quiz_correct' => 8, 'quiz_total' => 10,
            'metrics' => array_map(fn ($id) => ['id' => $id, 'earned' => 1, 'possible' => 1], ReadinessService::CRITERIA[$scenario])];
    }

    public function test_registration_is_not_started_and_admin_lists_players_without_progress_rows(): void
    {
        $this->postJson('/api/register', ['username' => 'newplayer', 'email' => 'new@example.test', 'password' => 'secret123', 'password_confirmation' => 'secret123'])->assertOk();
        $user = User::where('username', 'newplayer')->firstOrFail();
        $this->assertSame('not_started', $this->summary($user)['status']);
        $this->assertSame(0, $this->summary($user)['progress_percentage']);
        $this->assertNull($this->summary($user)['last_activity_at']);
        $this->assertDatabaseCount('player_progress', 0);
        $this->actingAs($this->makeAdmin(), 'web')->get('/admin/progress')->assertOk()
            ->assertSee('newplayer')->assertSee('Not Started')->assertSee('0/4')->assertSee('0%')->assertSee('data-player-progress', false);
    }

    public function test_start_uses_existing_token_and_only_changes_authenticated_player(): void
    {
        $user = $this->makeUser(); $other = $this->makeUser();
        Sanctum::actingAs($user, ['scores:submit']);
        $this->postJson('/api/progress/start', ['scenario' => 'volcanic-eruption'])->assertOk()
            ->assertJsonPath('scenario', 'Volcano')->assertJsonPath('progress.status', 'in_progress')
            ->assertJsonPath('progress.progress_percentage', 0)->assertJsonPath('progress.completed_scenarios', 0);
        $this->postJson('/api/progress/start', ['scenario' => 'Volcanic Eruption'])->assertOk();
        $this->assertDatabaseCount('player_progress', 1);
        $this->assertDatabaseHas('player_progress', ['user_id' => $user->id, 'scenario_slug' => 'volcano', 'status' => 'in_progress']);
        $this->assertSame('not_started', $this->summary($other)['status']);
        $this->assertDatabaseCount('leaderboard', 0);
        $this->assertDatabaseCount('readiness_progress', 0);
    }

    public function test_actual_completions_advance_in_quarters_and_survive_retries_failed_attempts_and_score_deletion(): void
    {
        $user = $this->makeUser(); Sanctum::actingAs($user, ['scores:submit']);
        foreach (array_keys(ReadinessService::CRITERIA) as $index => $scenario) {
            $attempt = $this->attempt($scenario);
            $this->postJson('/api/readiness/attempts', $attempt)->assertOk();
            $this->postJson('/api/readiness/attempts', $attempt)->assertOk();
            $this->postJson('/api/readiness/attempts', $this->attempt($scenario, false))->assertOk();
            $summary = $this->summary($user);
            $this->assertSame(($index + 1) * 25, $summary['progress_percentage']);
            $this->assertSame($index === 3 ? 'completed' : 'in_progress', $summary['status']);
        }
        $this->postJson('/api/progress/start', ['scenario' => 'Fire'])->assertJsonPath('progress.status', 'completed');
        DB::table('readiness_progress')->where('user_id', $user->id)->delete();
        $this->assertSame(100, $this->summary($user)['progress_percentage']);
        $this->assertCount(4, $this->summary($user)['completed_names']);
        $this->assertDatabaseCount('readiness_attempts', 8);
    }

    public function test_failed_attempt_is_activity_but_a_legacy_score_or_login_is_not_completion_or_start(): void
    {
        $user = $this->makeUser(['last_login_at' => now()]); Sanctum::actingAs($user, ['scores:submit']);
        $this->postJson('/api/save_score', ['category' => 'Fire', 'score' => 100])->assertOk()->assertJsonPath('best_score', 100);
        $this->assertSame('not_started', $this->summary($user)['status']);
        $this->postJson('/api/readiness/attempts', $this->attempt('Fire', false))->assertOk();
        $this->assertSame('in_progress', $this->summary($user)['status']);
        $this->assertSame(0, $this->summary($user)['completed_scenarios']);
        $this->assertNotNull($this->summary($user)['last_activity_at']);
    }

    public function test_historical_aliases_and_multiple_sources_do_not_double_count_or_rewrite_data(): void
    {
        $user = $this->makeUser();
        foreach (['volcano', 'Volcanic Eruption', 'volcanic-eruption'] as $slug) {
            PlayerProgress::create(['user_id' => $user->id, 'scenario_slug' => $slug, 'status' => 'completed', 'score' => 77]);
        }
        PlayerProgress::create(['user_id' => $user->id, 'scenario_slug' => 'unknown', 'status' => 'completed', 'score' => 100]);
        PlayerProgress::create(['user_id' => $user->id, 'scenario_slug' => 'fire', 'status' => 'not_started', 'score' => 100]);
        app(ReadinessService::class)->submit($user, $this->attempt('Volcano'));
        $before = DB::table('player_progress')->get()->toJson();
        $this->assertSame(25, $this->summary($user)['progress_percentage']);
        $this->assertSame(['Volcanic Eruption'], $this->summary($user)['completed_names']);
        $this->assertSame($before, DB::table('player_progress')->get()->toJson());
        app(PlayerProgressService::class)->start($user, 'Volcano');
        $this->assertDatabaseHas('player_progress', ['user_id' => $user->id, 'scenario_slug' => 'volcano', 'status' => 'completed', 'score' => 77]);
        $this->assertSame(25, $this->summary($user)['progress_percentage']);
    }

    public function test_progress_persists_through_logout_and_login_with_real_tokens(): void
    {
        $user = $this->makeUser(['password' => 'secret123']);
        $token = $user->createToken('Unity', ['scores:submit'])->plainTextToken;
        $this->withToken($token)->postJson('/api/progress/start', ['scenario' => 'Earthquake'])->assertOk();
        Auth::forgetGuards();
        $this->withToken($token)->postJson('/api/readiness/attempts', $this->attempt('Earthquake'))->assertOk();
        Auth::forgetGuards(); $this->withToken($token)->postJson('/api/logout')->assertOk();
        Auth::forgetGuards();
        $new = $this->postJson('/api/login', ['username' => $user->username, 'password' => 'secret123'])->assertOk()->json('token');
        Auth::forgetGuards();
        $this->withToken($new)->postJson('/api/progress/start', ['scenario' => 'Earthquake'])
            ->assertOk()->assertJsonPath('progress.progress_percentage', 25);
    }

    public function test_feed_revalidates_unchanged_data_and_returns_updated_progress_without_sensitive_fields(): void
    {
        $player = $this->makeUser(['username' => '<script>player</script>']); $admin = $this->makeAdmin();
        $this->actingAs($admin, 'web');
        $feed = $this->getJson('/admin/progress/data')->assertOk()->assertJsonStructure(['html']);
        $html = $feed->json('html');
        $this->assertStringContainsString('&lt;script&gt;player&lt;/script&gt;', $html);
        $this->assertStringNotContainsString($player->email, $html);
        $this->assertStringNotContainsString($player->password_hash, $html);
        $this->getJson('/admin/progress/data', ['If-None-Match' => $feed->headers->get('ETag')])->assertStatus(304);
        app(PlayerProgressService::class)->start($player, 'Fire');
        $changed = $this->getJson('/admin/progress/data', ['If-None-Match' => $feed->headers->get('ETag')])->assertOk();
        $this->assertStringContainsString('In Progress', $changed->json('html'));
        $this->assertNotSame($feed->headers->get('ETag'), $changed->headers->get('ETag'));
    }

    public function test_admin_filters_and_ten_player_pagination_use_overall_status_with_bounded_queries(): void
    {
        $admin = $this->makeAdmin();
        for ($i = 0; $i < 12; $i++) $this->makeUser(['username' => sprintf('player%02d', $i)]);
        $active = $this->makeUser(['username' => 'activeplayer']);
        app(PlayerProgressService::class)->start($active, 'Fire');
        DB::enableQueryLog();
        $rows = app(PlayerProgressService::class)->players()->paginate(10);
        foreach ($rows as $row) app(PlayerProgressService::class)->describe($row);
        $queries = DB::getQueryLog(); DB::disableQueryLog();
        $this->assertCount(2, $queries); $this->assertSame(13, $rows->total()); $this->assertCount(10, $rows);
        $this->actingAs($admin, 'web')->get('/admin/progress?status=in_progress')->assertOk()->assertSee('activeplayer')->assertDontSee('player00');
        $html = $this->getJson('/admin/progress/data?search=player&status=not_started&page=2')->assertOk()->json('html');
        $this->assertStringContainsString('player11', $html);
        $this->assertStringNotContainsString('/admin/progress/data?', $html);
        $this->assertStringContainsString('/admin/progress?', $html);
    }

    public function test_progress_endpoints_enforce_authentication_role_activity_and_token_scope(): void
    {
        $this->postJson('/api/progress/start', ['scenario' => 'Fire'])->assertUnauthorized();
        $this->getJson('/admin/progress/data')->assertUnauthorized();
        $user = $this->makeUser();
        $this->actingAs($user, 'web')->getJson('/admin/progress/data')->assertForbidden();
        Sanctum::actingAs($user, ['profile:read']);
        $this->postJson('/api/progress/start', ['scenario' => 'Fire'])->assertForbidden();
        $user->update(['is_active' => false]); Sanctum::actingAs($user, ['scores:submit']);
        $this->postJson('/api/progress/start', ['scenario' => 'Fire'])->assertForbidden();
        $admin = $this->makeAdmin(['is_active' => false]);
        $this->actingAs($admin, 'web')->getJson('/admin/progress/data')->assertForbidden();
        $this->assertDatabaseCount('player_progress', 0);
    }

    public function test_start_rejects_unknown_scenarios_forged_completion_and_other_player_ids(): void
    {
        Sanctum::actingAs($this->makeUser(), ['scores:submit']);
        foreach ([[], ['scenario' => 'Flood'], ['scenario' => []], ['scenario' => 'Fire', 'user_id' => 999], ['scenario' => 'Fire', 'completed' => true], ['scenario' => 'Fire', 'score' => 100]] as $data) {
            $this->postJson('/api/progress/start', $data)->assertUnprocessable();
        }
        $this->assertDatabaseCount('player_progress', 0);
        $this->assertDatabaseCount('readiness_progress', 0);
    }
}

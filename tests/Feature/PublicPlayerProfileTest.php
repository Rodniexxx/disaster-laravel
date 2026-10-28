<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\AvatarService;
use App\Services\CompletionCertificateService;
use App\Services\PlayerProgressService;
use App\Services\ReadinessService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Tests\TestCase;

class PublicPlayerProfileTest extends TestCase
{
    use RefreshDatabase;

    private function attempt(User $user, string $scenario, int $correct = 8, bool $completed = true): void
    {
        app(ReadinessService::class)->submit($user, [
            'attempt_id' => (string) Str::uuid(), 'scenario' => $scenario,
            'completed' => $completed, 'quiz_correct' => $correct, 'quiz_total' => 10,
            'metrics' => array_map(fn ($id) => ['id' => $id, 'earned' => 1, 'possible' => 1], ReadinessService::CRITERIA[$scenario]),
        ]);
    }

    public function test_each_leaderboard_player_has_one_profile_button_for_their_avatar_and_name(): void
    {
        $first = $this->makeUser(['username' => 'first']);
        $second = $this->makeUser(['username' => 'second']);
        $this->attempt($first, 'Fire');
        $this->attempt($second, 'Fire');

        foreach (['Overall', 'Fire'] as $category) {
            $page = $this->get(route('leaderboard.index', ['category' => $category]))->assertOk();
            foreach ([$first, $second] as $player) {
                $page->assertSee('data-player-profile="'.route('leaderboard.player', $player, false).'"', false)
                    ->assertSee('View '.e($player->username)."'s player profile", false);
            }
            $page->assertSee('aria-haspopup="dialog"', false)->assertSee('id="player-profile-dialog"', false);
        }
    }

    public function test_popup_requests_stay_on_the_page_origin_behind_an_https_proxy(): void
    {
        $player = $this->makeUser();
        $this->attempt($player, 'Fire');

        // A TLS-terminating proxy may leave Laravel generating HTTP URLs.
        URL::forceScheme('http');
        URL::forceRootUrl('http://internal.example');

        try {
            $page = $this->get('https://mangno.example/leaderboard')->assertOk();
            preg_match('/data-player-profile="([^"]+)"/', $page->getContent(), $match);
            $endpoint = $match[1] ?? '';

            $this->assertSame('/leaderboard/players/'.$player->id, $endpoint);
            $this->assertNull(parse_url($endpoint, PHP_URL_SCHEME));
            $this->assertNull(parse_url($endpoint, PHP_URL_HOST));
            $this->getJson($endpoint)->assertOk()->assertJsonPath('username', $player->username);
        } finally {
            URL::forceScheme(null);
            URL::forceRootUrl(null);
        }
    }

    public function test_public_scores_and_rank_match_the_leaderboard_including_ties(): void
    {
        $first = $this->makeUser();
        $second = $this->makeUser();
        foreach ([$first, $second] as $user) {
            $this->attempt($user, 'Fire', 8);
            $this->attempt($user, 'Typhoon', 5);
        }
        $rows = app(ReadinessService::class)->leaderboard('Overall');
        foreach ([$first, $second] as $index => $user) {
            $this->getJson(route('leaderboard.player', $user))->assertOk()
                ->assertJsonPath('username', $user->username)
                ->assertJsonPath('rank', $rows[$index]['rank'])
                ->assertJsonPath('total_score', $rows[$index]['score'])
                ->assertJsonPath('total_score', 172)
                ->assertJsonPath('max_score', 400)
                ->assertJsonPath('completed_scenarios', 2)
                ->assertJsonPath('progress_percent', 50)
                ->assertJsonPath('stars', 5)
                ->assertJsonCount(4, 'scenarios')
                ->assertJsonFragment(['name' => 'Fire', 'best_score' => 92, 'status' => 'completed', 'status_label' => 'Completed', 'stars' => 3]);
        }
    }

    public function test_a_new_player_has_empty_states_and_a_default_avatar(): void
    {
        $player = $this->makeUser();
        $data = $this->getJson(route('leaderboard.player', $player))->assertOk()
            ->assertJsonPath('rank', null)->assertJsonPath('total_score', 0)
            ->assertJsonPath('avatar_url', app(AvatarService::class)->defaultUrl())
            ->assertJsonPath('progress_percent', 0)->assertJsonPath('stars', 0)
            ->assertJsonPath('perfect_scores', 0)->assertJsonPath('certificate_status', 'Not earned')
            ->assertJsonCount(0, 'achievements')->json();

        $this->assertSame(['Earthquake', 'Fire', 'Typhoon', 'Volcanic Eruption'], array_column($data['scenarios'], 'name'));
        foreach ($data['scenarios'] as $scenario) {
            $this->assertNull($scenario['best_score']);
            $this->assertSame('Not Started', $scenario['status_label']);
            $this->assertSame(0, $scenario['stars']);
        }
    }

    public function test_saved_attempts_and_unity_starts_show_in_progress_without_inventing_scores(): void
    {
        $player = $this->makeUser();
        $this->attempt($player, 'Earthquake', completed: false);
        app(PlayerProgressService::class)->start($player, 'Fire');
        $this->getJson(route('leaderboard.player', $player))->assertOk()
            ->assertJsonFragment(['name' => 'Earthquake', 'best_score' => null, 'status' => 'in_progress', 'status_label' => 'In Progress', 'stars' => 0])
            ->assertJsonFragment(['name' => 'Fire', 'best_score' => null, 'status' => 'in_progress', 'status_label' => 'In Progress', 'stars' => 0])
            ->assertJsonPath('total_score', 0)->assertJsonPath('completed_scenarios', 0);
    }

    public function test_a_legacy_completion_is_recognized_without_changing_readiness_scores_or_awarding_stars(): void
    {
        $player = $this->makeUser();
        $player->progress()->create(['scenario_slug' => 'volcano', 'status' => 'completed', 'score' => 999]);
        $this->getJson(route('leaderboard.player', $player))->assertOk()
            ->assertJsonPath('completed_scenarios', 1)->assertJsonPath('progress_percent', 25)
            ->assertJsonPath('rank', null)->assertJsonPath('total_score', 0)->assertJsonPath('stars', 0)
            ->assertJsonFragment(['name' => 'Volcanic Eruption', 'best_score' => null, 'status' => 'completed', 'status_label' => 'Completed', 'stars' => 0]);
    }

    public function test_only_saved_achievements_are_shown_and_viewing_never_issues_a_certificate(): void
    {
        $player = $this->makeUser();
        foreach (array_keys(ReadinessService::CRITERIA) as $scenario) {
            $this->attempt($player, $scenario, 10);
        }
        $this->getJson(route('leaderboard.player', $player))->assertOk()
            ->assertJsonPath('stars', 12)->assertJsonPath('perfect_scores', 4)
            ->assertJsonFragment(['MANGNO Master'])
            ->assertJsonPath('certificate_status', 'Eligible, not issued');
        $this->assertDatabaseCount('completion_certificates', 0);

        DB::table('readiness_achievements')->where('user_id', $player->id)->where('achievement_id', 'mangno_master')->delete();
        $response = $this->getJson(route('leaderboard.player', $player))->assertOk();
        $this->assertNotContains('MANGNO Master', $response->json('achievements'));
        $response->assertJsonPath('certificate_status', 'Not earned');
        $this->assertDatabaseCount('completion_certificates', 0);
    }

    public function test_issued_certificate_status_is_public_but_its_identifier_and_account_data_are_not(): void
    {
        $player = $this->makeUser(['username' => '<script>alert(1)</script>']);
        foreach (array_keys(ReadinessService::CRITERIA) as $scenario) {
            $this->attempt($player, $scenario, 10);
        }
        $certificate = app(CompletionCertificateService::class)->issue($player);
        $response = $this->getJson(route('leaderboard.player', $player))->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertJsonPath('certificate_status', 'Issued');
        $data = $response->json();
        $this->assertSame([
            'username', 'avatar_url', 'joined', 'rank', 'total_score', 'max_score',
            'completed_scenarios', 'total_scenarios', 'progress_percent', 'stars',
            'max_stars', 'perfect_scores', 'scenarios', 'achievements', 'certificate_status',
        ], array_keys($data));
        $response->assertDontSee($player->email)->assertDontSee($player->password_hash)
            ->assertDontSee($certificate['certificate_id']);
        $this->get('/leaderboard')->assertOk()->assertDontSee('<script>alert(1)</script>', false);
        $this->assertDatabaseCount('completion_certificates', 1);
    }

    public function test_missing_or_inactive_players_are_not_public_and_the_endpoint_is_read_only(): void
    {
        $inactive = $this->makeUser(['is_active' => false]);
        $this->getJson(route('leaderboard.player', $inactive))->assertNotFound();
        $this->getJson('/leaderboard/players/999999')->assertNotFound();
        $this->getJson('/leaderboard/players/not-a-number')->assertNotFound();
        $player = $this->makeUser();
        $this->postJson(route('leaderboard.player', $player), ['score' => 400])->assertStatus(405);
        $this->patchJson(route('leaderboard.player', $player), ['username' => 'changed'])->assertStatus(405);
        $this->assertDatabaseCount('readiness_progress', 0);
        $this->assertDatabaseCount('readiness_achievements', 0);
        $this->assertSame($player->username, $player->refresh()->username);
    }

    public function test_the_existing_avatar_url_is_used_without_exposing_storage_details(): void
    {
        $player = $this->makeUser();
        $version = (string) Str::uuid();
        $player->playerProfile()->create([
            'display_name' => 'Private profile label',
            'avatar_path' => 'avatars/'.$player->id.'/'.$version.'.jpg',
            'avatar_disk' => 'profile_volume',
            'avatar_version' => $version,
            'revision' => 1,
        ]);
        $this->getJson(route('leaderboard.player', $player))->assertOk()
            ->assertJsonPath('avatar_url', $player->profile_picture_url)
            ->assertDontSee('avatar_disk')->assertDontSee('avatar_path')
            ->assertDontSee('Private profile label');
    }
}

<?php

namespace Tests\Feature;

use App\Models\Leaderboard;
use App\Models\User;
use App\Services\PlayerStatsService;
use App\Services\ReadinessService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * The player dashboard and the leaderboard must show the same numbers for the
 * same player, because both read the same readiness_progress records.
 */
class PlayerDashboardLeaderboardConsistencyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Save a readiness attempt exactly like the Unity client does.
     */
    private function attempt(User $user, string $scenario, int $correct = 8, bool $completed = true): array
    {
        return app(ReadinessService::class)->submit($user, [
            'attempt_id' => (string) Str::uuid(), 'scenario' => $scenario,
            'completed' => $completed, 'quiz_correct' => $correct, 'quiz_total' => 10,
            'metrics' => array_map(fn ($id) => ['id' => $id, 'earned' => 1, 'possible' => 1], ReadinessService::CRITERIA[$scenario]),
        ]);
    }

    /**
     * "jake" with 92 (Fire) + 80 (Typhoon) = 172, behind three players on 200.
     */
    private function makeJakeRankedFourth(): User
    {
        $jake = $this->makeUser(['username' => 'jake']);
        $this->attempt($jake, 'Fire', 8);
        $this->attempt($jake, 'Typhoon', 5);

        foreach (['ann', 'bob', 'cat'] as $name) {
            $rival = $this->makeUser(['username' => $name]);
            $this->attempt($rival, 'Fire', 10);
            $this->attempt($rival, 'Typhoon', 10);
        }

        return $jake;
    }

    public function test_a_player_with_saved_scores_appears_consistently_on_the_dashboard_and_the_leaderboard(): void
    {
        $jake = $this->makeJakeRankedFourth();

        $dashboard = $this->actingAs($jake)->get('/dashboard')->assertOk();
        $leaderboard = $this->get('/leaderboard')->assertOk();

        $stats = $dashboard->viewData('stats');
        $row = collect($leaderboard->viewData('rows')->items())
            ->firstWhere('username', 'jake');

        // The very same total on both pages.
        $this->assertSame(172, $stats['overall']);
        $this->assertSame(172, $row['score']);
        // ... and the very same rank.
        $this->assertSame($row['rank'], $stats['rank']);
    }

    public function test_a_player_with_an_overall_score_of_172_sees_that_aggregate_and_rank_on_the_dashboard(): void
    {
        $jake = $this->makeJakeRankedFourth();

        $response = $this->actingAs($jake)->get('/dashboard')->assertOk();

        $stats = $response->viewData('stats');
        $this->assertSame(172, $stats['overall']);
        $this->assertSame(400, $stats['overallMax']);
        $this->assertSame(4, $stats['rank']);

        // The rendered dashboard carries the same numbers.
        $response->assertSee('172')
            ->assertSee('/400')
            ->assertSee('#4');

        // The leaderboard agrees, and the Overall leaderboard really is a
        // total of at most 100 per disaster.
        $this->get('/leaderboard')->assertOk()
            ->assertSee('jake')
            ->assertViewHas('rows', fn ($rows) => collect($rows->items())
                ->firstWhere('username', 'jake')['score'] === 172);
    }

    public function test_the_best_score_is_the_highest_individual_scenario_score(): void
    {
        $jake = $this->makeJakeRankedFourth();

        $stats = $this->actingAs($jake)->get('/dashboard')->assertOk()->viewData('stats');

        // Fire 92 and Typhoon 80 were completed.
        $this->assertSame(92, $stats['best']);
        $this->assertSame(92, $stats['perCategory']['fire']);
        $this->assertSame(80, $stats['perCategory']['typhoon']);
        $this->assertSame(172, $stats['overall']);
    }

    public function test_a_player_without_scores_sees_the_empty_state(): void
    {
        $player = $this->makeUser(['username' => 'newcomer']);

        $response = $this->actingAs($player)->get('/dashboard')->assertOk();

        $stats = $response->viewData('stats');
        $this->assertSame(0, $stats['overall']);
        $this->assertSame(0, $stats['best']);
        $this->assertNull($stats['rank']);
        $this->assertSame(
            ['not_started', 'not_started', 'not_started', 'not_started'],
            array_values($stats['status']),
        );

        $response->assertSee('N/A')->assertSee('Not Started');

        // The player is not in the rankings either.
        $this->get('/leaderboard')->assertOk()
            ->assertViewHas('rows', fn ($rows) => collect($rows->items())
                ->firstWhere('username', 'newcomer') === null);
    }

    public function test_scenario_progress_is_mapped_to_the_correct_disaster_category(): void
    {
        $player = $this->makeUser(['username' => 'mapper']);

        $this->attempt($player, 'Earthquake', 8);
        $this->attempt($player, 'Fire', 6);
        $this->attempt($player, 'Typhoon', 5);
        $this->attempt($player, 'Volcano', 9);

        $stats = app(PlayerStatsService::class)->forUser($player);

        $this->assertSame(92, $stats['perCategory']['earthquake']);
        $this->assertSame(84, $stats['perCategory']['fire']);
        $this->assertSame(80, $stats['perCategory']['typhoon']);
        // "Volcano" is stored under its canonical category and shown as
        // "Volcanic Eruption".
        $this->assertSame(96, $stats['perCategory']['volcano']);
        $this->assertSame(
            ['earthquake' => 'completed', 'fire' => 'completed', 'typhoon' => 'completed', 'volcano' => 'completed'],
            $stats['status'],
        );
        $this->assertSame(352, $stats['overall']);

        // The card shows the saved score next to the right disaster.
        $this->actingAs($player)->get('/dashboard')->assertOk()
            ->assertSee('Volcanic Eruption')
            ->assertSeeText('96/100');
    }

    public function test_a_started_but_unfinished_scenario_is_not_reported_as_not_started(): void
    {
        $player = $this->makeUser(['username' => 'quitter']);

        // An attempt that did not meet the completion rules.
        $this->attempt($player, 'Earthquake', 8, false);

        $stats = app(PlayerStatsService::class)->forUser($player);

        $this->assertSame('in_progress', $stats['status']['earthquake']);
        $this->assertFalse($stats['hasScore']['earthquake']);
        // Nothing is completed, so there is no total and no ranking.
        $this->assertSame(0, $stats['overall']);
        $this->assertNull($stats['rank']);
    }

    public function test_scores_submitted_through_the_unity_api_are_visible_on_both_pages(): void
    {
        $player = $this->makeUser(['username' => 'unityplayer']);
        $token = $player->createToken('unity', ['scores:submit'])->plainTextToken;

        $this->withToken($token)->postJson('/api/readiness/attempts', [
            'attempt_id' => (string) Str::uuid(), 'scenario' => 'Fire',
            'completed' => true, 'quiz_correct' => 8, 'quiz_total' => 10,
            'metrics' => array_map(fn ($id) => ['id' => $id, 'earned' => 1, 'possible' => 1], ReadinessService::CRITERIA['Fire']),
        ])->assertOk()->assertJsonPath('overall_score', 92);

        // The dashboard picks the submission up.
        $stats = $this->actingAs($player)->get('/dashboard')->assertOk()->viewData('stats');
        $this->assertSame(92, $stats['overall']);
        $this->assertSame(92, $stats['best']);
        $this->assertSame(1, $stats['rank']);
        $this->assertSame('completed', $stats['status']['fire']);

        // ... and so does the leaderboard.
        $this->get('/leaderboard')->assertOk()
            ->assertSee('unityplayer')
            ->assertViewHas('rows', fn ($rows) => $rows->items()[0]['score'] === 92);
    }

    public function test_the_unity_leaderboard_api_contracts_are_unchanged(): void
    {
        $jake = $this->makeJakeRankedFourth();
        Leaderboard::create([
            'user_id' => $jake->id, 'username' => 'legacy_player', 'category' => 'Fire', 'score' => 77,
        ]);

        // The readiness leaderboard keeps its exact row shape.
        $readiness = $this->getJson('/api/readiness/leaderboard?category=Overall')->assertOk();
        $readiness->assertJsonPath('success', true);
        $this->assertSame(['rank', 'username', 'category', 'score'], array_keys($readiness->json('leaderboard.0')));
        $this->assertSame('jake', $readiness->json('leaderboard.3.username'));
        $this->assertSame(172, $readiness->json('leaderboard.3.score'));
        $this->assertSame(4, $readiness->json('leaderboard.3.rank'));

        // The legacy leaderboard keeps its exact row shape and its own records.
        $legacy = $this->getJson('/api/get_leaderboard?category=Fire')->assertOk();
        $legacy->assertJsonPath('success', true);
        $this->assertSame(['rank', 'username', 'category', 'score'], array_keys($legacy->json('leaderboard.0')));
        $this->assertSame('legacy_player', $legacy->json('leaderboard.0.username'));
        $this->assertSame(77, $legacy->json('leaderboard.0.score'));
    }

    public function test_legacy_scores_never_leak_into_the_dashboard_or_the_leaderboard(): void
    {
        $player = $this->makeUser(['username' => 'legacyonly']);

        Leaderboard::create([
            'user_id' => $player->id, 'username' => 'legacyonly', 'category' => 'Fire', 'score' => 99,
        ]);

        $stats = $this->actingAs($player)->get('/dashboard')->assertOk()->viewData('stats');

        $this->assertSame(0, $stats['overall']);
        $this->assertSame(0, $stats['best']);
        $this->assertNull($stats['rank']);
        $this->assertSame('not_started', $stats['status']['fire']);

        $this->get('/leaderboard')->assertOk()
            ->assertViewHas('rows', fn ($rows) => collect($rows->items())
                ->firstWhere('username', 'legacyonly') === null);
    }

    public function test_deleting_a_score_in_the_admin_leaderboard_updates_the_dashboard_and_the_leaderboard(): void
    {
        $admin = $this->makeAdmin();
        $jake = $this->makeJakeRankedFourth();

        $before = $this->actingAs($jake)->get('/dashboard')->assertOk()->viewData('stats');
        $this->assertSame(172, $before['overall']);
        $this->assertSame(4, $before['rank']);

        $this->actingAs($admin)
            ->delete('/admin/leaderboard/scores', [
                'user_id' => $jake->id,
                'scenario' => 'Fire',
                'category' => 'Fire',
            ])
            ->assertRedirect(route('admin.leaderboard', ['category' => 'Fire']))
            ->assertSessionHas('status');

        // The dashboard drops the deleted score and is re-ranked.
        $after = $this->actingAs($jake)->get('/dashboard')->assertOk()->viewData('stats');
        $this->assertSame(80, $after['overall']);
        $this->assertSame(80, $after['best']);
        $this->assertSame(4, $after['rank']);
        // The personal best is gone, but the attempt history is kept, so the
        // scenario counts as started again instead of never played.
        $this->assertSame('in_progress', $after['status']['fire']);
        $this->assertSame(80, $after['perCategory']['typhoon']);

        // The leaderboard agrees.
        $this->get('/leaderboard')->assertOk()
            ->assertViewHas('rows', fn ($rows) => collect($rows->items())
                ->firstWhere('username', 'jake')['score'] === 80);
    }

    public function test_the_my_scores_page_lists_the_same_saved_records(): void
    {
        $player = $this->makeUser(['username' => 'scorer']);
        $this->attempt($player, 'Fire', 8);
        $this->attempt($player, 'Volcano', 9);

        // A legacy quiz record for a category without a readiness record.
        Leaderboard::create([
            'user_id' => $player->id, 'username' => 'scorer', 'category' => 'Typhoon', 'score' => 40,
        ]);

        $rows = $this->actingAs($player)->get('/scores')->assertOk()->viewData('scores');

        $this->assertSame(
            ['Fire' => 92, 'Typhoon' => 40, 'Volcanic Eruption' => 96],
            $rows->mapWithKeys(fn ($row) => [$row['category'] => $row['score']])->all(),
        );
    }

    public function test_the_dashboard_and_the_leaderboard_use_the_same_authenticated_account(): void
    {
        $jake = $this->makeJakeRankedFourth();
        $other = $this->makeUser(['username' => 'other']);

        $this->actingAs($other)->get('/dashboard')->assertOk()
            ->assertViewHas('stats', fn ($stats) => $stats['overall'] === 0);

        $stats = $this->actingAs($jake)->get('/dashboard')->assertOk()->viewData('stats');
        $this->assertSame(172, $stats['overall']);

        // A second player never sees the first player's totals.
        $this->assertDatabaseHas('readiness_progress', ['user_id' => $jake->id, 'scenario' => 'Fire']);
        $this->assertDatabaseMissing('readiness_progress', ['user_id' => $other->id]);
    }
}

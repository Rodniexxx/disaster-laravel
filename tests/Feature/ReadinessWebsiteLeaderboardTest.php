<?php

namespace Tests\Feature;

use App\Models\Leaderboard;
use App\Models\User;
use App\Services\ReadinessService;
use App\Support\DisasterCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class ReadinessWebsiteLeaderboardTest extends TestCase
{
    use RefreshDatabase;

    private function submitAttempt(User $user, string $scenario, int $correct = 8, bool $completed = true): array
    {
        return app(ReadinessService::class)->submit($user, [
            'attempt_id' => (string) Str::uuid(), 'scenario' => $scenario,
            'completed' => $completed, 'quiz_correct' => $correct, 'quiz_total' => 10,
            'metrics' => array_map(fn ($id) => ['id' => $id, 'earned' => 1, 'possible' => 1], ReadinessService::CRITERIA[$scenario]),
        ]);
    }

    public function test_public_and_admin_rankings_match_unity_for_every_category(): void
    {
        $jake = $this->makeUser(['username' => 'jake']);
        $other = $this->makeUser(['username' => 'other']);
        foreach (array_keys(ReadinessService::CRITERIA) as $scenario) {
            $this->submitAttempt($jake, $scenario, 3); // 60 gameplay contribution + 12 quiz = 72.
            $this->submitAttempt($other, $scenario, 8);
        }
        $inactive = $this->makeUser(['is_active' => false]);
        $unfinished = $this->makeUser();
        $this->submitAttempt($inactive, 'Volcano', 10);
        $this->submitAttempt($unfinished, 'Volcano', 10, false);
        Leaderboard::create(['user_id' => $jake->id, 'username' => 'legacy_only', 'category' => 'Volcano', 'score' => 100]);

        $admin = $this->makeAdmin();
        foreach (['Overall', ...array_keys(ReadinessService::CRITERIA)] as $category) {
            $api = $this->getJson('/api/readiness/leaderboard?category='.$category)->assertOk()->json('leaderboard');
            $expected = array_map(function ($row) {
                $row['category'] = app(DisasterCatalog::class)->displayName($row['category']) ?? $row['category'];
                return $row;
            }, $api);
            $this->assertCount(2, $expected);
            $public = $this->get('/leaderboard?category='.$category)->assertOk();
            $this->assertSame($expected, $public->viewData('rows')->items());
            $adminPage = $this->actingAs($admin)->get('/admin/leaderboard?category='.$category)->assertOk();
            $this->assertSame($expected, $adminPage->viewData('rows')->items());
        }
        $this->assertDatabaseHas('leaderboard', ['username' => 'legacy_only', 'score' => 100]);
        $this->assertDatabaseCount('readiness_attempts', 10);
    }

    public function test_volcanic_eruption_aliases_show_jake_72_without_changing_any_records(): void
    {
        $jake = $this->makeUser(['username' => 'jake']);
        $this->submitAttempt($jake, 'Volcano', 2);
        $this->submitAttempt($jake, 'Volcano', 3);
        Leaderboard::create(['user_id' => $jake->id, 'username' => 'old_quiz_player', 'category' => 'Volcano', 'score' => 99]);
        $before = [];
        foreach (['users', 'leaderboard', 'readiness_attempts', 'readiness_progress', 'readiness_achievements'] as $table) {
            $before[$table] = DB::table($table)->orderBy('id')->get()->toJson();
        }
        foreach (['Volcano', 'volcano', 'Volcanic Eruption', 'volcanic eruption'] as $alias) {
            $page = $this->get('/leaderboard?category='.urlencode($alias))->assertOk()
                ->assertSee('Readiness score')->assertSee('Volcanic Eruption')->assertDontSee('old_quiz_player');
            $this->assertSame([['rank' => 1, 'username' => 'jake', 'category' => 'Volcanic Eruption', 'score' => 72]], $page->viewData('rows')->items());
        }
        $this->getJson('/api/readiness/leaderboard?category=Volcano')->assertOk()
            ->assertJsonPath('leaderboard.0.username', 'jake')->assertJsonPath('leaderboard.0.score', 72);
        $this->getJson('/api/get_leaderboard?category=Volcano')->assertOk()
            ->assertJsonPath('leaderboard.0.username', 'old_quiz_player')->assertJsonPath('leaderboard.0.score', 99);
        foreach ($before as $table => $snapshot) {
            $this->assertSame($snapshot, DB::table($table)->orderBy('id')->get()->toJson(), $table.' changed during read-only rankings');
        }
    }

    public function test_overall_sums_completed_bests_and_uses_the_same_tie_break_as_unity(): void
    {
        $first = $this->makeUser(['username' => 'first']);
        $second = $this->makeUser(['username' => 'second']);
        $this->submitAttempt($first, 'Fire', 8);
        $this->submitAttempt($first, 'Fire', 0); // Lower replay must not replace 92.
        $this->submitAttempt($first, 'Typhoon', 5); // 80; overall is 172, missing scenarios zero.
        $this->submitAttempt($first, 'Volcano', 10, false);
        $this->submitAttempt($second, 'Fire', 8);
        $this->submitAttempt($second, 'Typhoon', 5);
        $api = $this->getJson('/api/readiness/leaderboard?category=Overall')->assertOk()->json('leaderboard');
        $this->assertSame(['first', 'second'], array_column($api, 'username'));
        $this->assertSame([172, 172], array_column($api, 'score'));
        $this->assertSame($api, $this->get('/leaderboard')->assertOk()->viewData('rows')->items());
    }

    public function test_pagination_keeps_unity_ranks_and_its_top_100_limit(): void
    {
        for ($i = 0; $i < 105; $i++) {
            $this->submitAttempt($this->makeUser(), 'Fire', 8);
        }
        $api = $this->getJson('/api/readiness/leaderboard?category=Fire')->assertOk()->json('leaderboard');
        $this->assertCount(100, $api);
        $combined = [];
        for ($page = 1; $page <= 5; $page++) {
            $response = $this->get('/leaderboard?category=Fire&page='.$page)->assertOk();
            $rows = $response->viewData('rows');
            $this->assertSame(100, $rows->total());
            $combined = [...$combined, ...$rows->items()];
            if ($page < 5) $this->assertStringContainsString('category=Fire', $rows->nextPageUrl());
        }
        $this->assertSame($api, $combined);
        $this->assertSame(21, $combined[20]['rank']);
        $this->get('/leaderboard?category=Fire&page=6')->assertOk()->assertViewHas('rows', fn ($rows) => $rows->isEmpty());
    }

    public function test_legacy_quiz_scores_do_not_populate_an_empty_readiness_page(): void
    {
        $user = $this->makeUser();
        Leaderboard::create(['user_id' => $user->id, 'username' => 'quiz_only', 'category' => 'Volcano', 'score' => 99]);
        $this->get('/leaderboard?category=Volcanic%20Eruption')->assertOk()
            ->assertSee('No readiness scores yet.')->assertDontSee('quiz_only')
            ->assertViewHas('rows', fn ($rows) => $rows->isEmpty());
        $this->assertDatabaseCount('leaderboard', 1);
        $this->assertDatabaseCount('readiness_progress', 0);
    }
}

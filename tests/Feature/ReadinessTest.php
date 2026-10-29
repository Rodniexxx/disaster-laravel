<?php

namespace Tests\Feature;

use App\Models\Leaderboard;
use App\Services\ReadinessService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ReadinessTest extends TestCase
{
    use RefreshDatabase;
    private function attempt(string $scenario = 'Typhoon', int $correct = 10, bool $completed = true): array
    {
        return ['attempt_id' => (string) Str::uuid(), 'scenario' => $scenario, 'completed' => $completed,
            'quiz_correct' => $correct, 'quiz_total' => 10,
            'metrics' => array_map(fn ($id) => ['id' => $id, 'earned' => 1.0, 'possible' => 1.0], ReadinessService::CRITERIA[$scenario])];
    }
    public function test_scoring_boundaries_and_incompletion(): void
    {
        foreach ([95 => 3, 90 => 3, 89 => 2, 80 => 2, 70 => 2, 69 => 1, 60 => 1, 0 => 1] as $score => $stars) {
            $this->assertSame($stars, ReadinessService::stars($score, true));
            $this->assertSame(0, ReadinessService::stars($score, false));
        }
        $this->assertSame(92, ReadinessService::overall(100, 80));
        $this->assertSame(95, ReadinessService::overall(91.6666667, 100));
    }
    public function test_best_attempt_pairs_are_preserved_and_retries_are_idempotent(): void
    {
        $user = $this->makeUser(); Sanctum::actingAs($user, ['scores:submit']);
        $first = $this->attempt('Typhoon', 8);
        $this->postJson('/api/readiness/attempts', $first)->assertOk()->assertJsonPath('overall_score', 92)
            ->assertJsonPath('stars', 3)->assertJsonCount(3, 'newly_unlocked');
        $this->postJson('/api/readiness/attempts', $first)->assertOk();
        $this->assertDatabaseCount('readiness_attempts', 1); $this->assertDatabaseCount('readiness_achievements', 3);
        $lower = $this->attempt('Typhoon', 1);
        $this->postJson('/api/readiness/attempts', $lower)->assertOk()->assertJsonCount(0, 'newly_unlocked');
        $this->assertDatabaseHas('readiness_progress', ['best_attempt_id' => $first['attempt_id'], 'best_gameplay_score' => 100, 'best_quiz_score' => 80, 'best_overall_score' => 92, 'stars' => 3]);
        $higher = $this->attempt();
        $this->postJson('/api/readiness/attempts', $higher)->assertOk();
        $this->assertDatabaseHas('readiness_progress', ['best_attempt_id' => $higher['attempt_id'], 'best_overall_score' => 100]);
        $first['quiz_correct'] = 7;
        $this->postJson('/api/readiness/attempts', $first)->assertUnprocessable();
    }
    public function test_earthquake_hard_evidence_persists_and_retries_do_not_duplicate(): void
    {
        $user = $this->makeUser(); Sanctum::actingAs($user, ['scores:submit']);
        $attempt = $this->attempt('Earthquake', 8);
        foreach (ReadinessService::EARTHQUAKE_HARD_CRITERIA as $id) {
            $earned = $id === 'hard_falling_objects_and_glass' ? 0.5 : 1.0;
            $attempt['metrics'][] = ['id' => $id, 'earned' => $earned, 'possible' => 1.0];
        }

        $this->postJson('/api/readiness/attempts', $attempt)->assertOk()
            ->assertJsonPath('gameplay_score', 94)->assertJsonPath('quiz_score', 80)
            ->assertJsonPath('overall_score', 88)->assertJsonPath('stars', 2);
        $this->postJson('/api/readiness/attempts', $attempt)->assertOk();
        $this->assertDatabaseCount('readiness_attempts', 1);
        $this->getJson('/api/readiness')->assertOk()
            ->assertJsonPath('progress.scenarios.1.scenario', 'Earthquake')
            ->assertJsonPath('progress.scenarios.1.completed', true)
            ->assertJsonPath('progress.scenarios.1.best_overall_score', 88)
            ->assertJsonPath('progress.scenarios.1.best_gameplay_score', 94)
            ->assertJsonPath('progress.scenarios.1.best_quiz_score', 80);
        $this->getJson('/api/readiness/leaderboard?category=Earthquake')->assertOk()
            ->assertJsonPath('leaderboard.0.score', 88);
        $other = $this->makeUser(); Sanctum::actingAs($other, ['scores:submit']);
        $this->getJson('/api/readiness')->assertOk()->assertJsonPath('progress.total_stars', 0);
        Sanctum::actingAs($user, ['scores:submit']);
        $this->getJson('/api/readiness')->assertOk()
            ->assertJsonPath('progress.scenarios.1.best_overall_score', 88);
    }
    public function test_all_achievements_and_new_overall_leaderboard_exclude_legacy_scores(): void
    {
        $user = $this->makeUser(); Sanctum::actingAs($user, ['scores:submit']);
        Leaderboard::create(['user_id' => $user->id, 'username' => $user->username, 'category' => 'Typhoon', 'score' => 99]);
        $this->getJson('/api/readiness/leaderboard')->assertOk()->assertJsonCount(0, 'leaderboard');
        foreach (array_keys(ReadinessService::CRITERIA) as $scenario) {
            $this->postJson('/api/readiness/attempts', $this->attempt($scenario))->assertOk();
            $this->getJson('/api/readiness/leaderboard?category='.$scenario)->assertOk()->assertJsonPath('leaderboard.0.score', 100);
        }
        $this->getJson('/api/readiness')->assertOk()->assertJsonPath('progress.total_stars', 12)
            ->assertJsonPath('progress.completed_scenarios', 4)->assertJsonPath('progress.mastered_scenarios', 4)->assertJsonCount(5, 'progress.achievements');
        $this->getJson('/api/readiness/leaderboard')->assertOk()->assertJsonPath('leaderboard.0.score', 400);
        $this->assertDatabaseHas('leaderboard', ['score' => 99]);
        $other = $this->makeUser(); Sanctum::actingAs($other, ['scores:submit']);
        $this->getJson('/api/readiness')->assertJsonPath('progress.total_stars', 0)->assertJsonCount(0, 'progress.achievements');
        $this->postJson('/api/readiness/attempts', $this->attempt('Fire', 5))->assertOk()->assertJsonPath('overall_score', 80);
        $this->getJson('/api/readiness/leaderboard')->assertJsonPath('leaderboard.1.score', 80);
        Sanctum::actingAs($user, ['scores:submit']);
        $this->getJson('/api/readiness')->assertJsonPath('progress.total_stars', 12);
    }
    public function test_validation_and_incomplete_attempts(): void
    {
        $this->getJson('/api/readiness')->assertUnauthorized();
        $user = $this->makeUser(); Sanctum::actingAs($user, ['scores:submit']);
        $incomplete = $this->attempt('Fire', 8, false);
        $incomplete['metrics'][2]['earned'] = 0;
        $this->postJson('/api/readiness/attempts', $incomplete)->assertOk()->assertJsonPath('stars', 0)
            ->assertJsonPath('progress.completed_scenarios', 0)->assertJsonCount(1, 'progress.achievements');
        $bad = $this->attempt(); $bad['scenario'] = 'Flood';
        $this->postJson('/api/readiness/attempts', $bad)->assertUnprocessable();
        $bad = $this->attempt(); $bad['metrics'][0]['earned'] = 2;
        $this->postJson('/api/readiness/attempts', $bad)->assertUnprocessable();
        $bad = $this->attempt(); $bad['metrics'][1] = $bad['metrics'][0];
        $this->postJson('/api/readiness/attempts', $bad)->assertUnprocessable();
        $bad = $this->attempt(); $bad['metrics'][1]['earned'] = 0;
        $this->postJson('/api/readiness/attempts', $bad)->assertUnprocessable();
        foreach (['user_id' => 9999, 'stars' => 12, 'quiz_total' => 9, 'quiz_correct' => 11] as $key => $value) {
            $bad = $this->attempt(); $bad[$key] = $value;
            $this->postJson('/api/readiness/attempts', $bad)->assertUnprocessable();
        }
        Sanctum::actingAs($user, ['profile:read']);
        $this->postJson('/api/readiness/attempts', $this->attempt())->assertForbidden();
    }
    public function test_real_evidence_yields_95_80_and_60_without_mixing_attempts(): void
    {
        $user = $this->makeUser(); Sanctum::actingAs($user, ['scores:submit']);
        $typhoon = $this->attempt();
        $typhoon['metrics'][0] = ['id' => 'preparation', 'earned' => 5, 'possible' => 6];
        $this->postJson('/api/readiness/attempts', $typhoon)->assertOk()->assertJsonPath('overall_score', 95)->assertJsonPath('stars', 3);
        $fire = $this->attempt('Fire', 5);
        $fire['metrics'][0]['earned'] = 0;
        $this->postJson('/api/readiness/attempts', $fire)->assertOk()->assertJsonPath('overall_score', 60)->assertJsonPath('stars', 1);
        $this->postJson('/api/readiness/attempts', $this->attempt('Fire', 5))->assertOk()->assertJsonPath('overall_score', 80)->assertJsonPath('stars', 2);
        // Better quiz with weaker gameplay is a lower overall result (76), and
        // must not combine its 90 quiz points with the previous 100 gameplay.
        $fire = $this->attempt('Fire', 9); $fire['metrics'][0]['earned'] = 0;
        $this->postJson('/api/readiness/attempts', $fire)->assertOk()->assertJsonPath('overall_score', 76);
        $this->assertDatabaseHas('readiness_progress', ['scenario' => 'Fire', 'best_gameplay_score' => 100,
            'best_quiz_score' => 50, 'best_overall_score' => 80, 'stars' => 2]);
    }
}

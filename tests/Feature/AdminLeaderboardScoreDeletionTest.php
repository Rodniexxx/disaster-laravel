<?php

namespace Tests\Feature;

use App\Services\ReadinessService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminLeaderboardScoreDeletionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * readiness_progress holds one personal best per (user, scenario) — the
     * record a single category leaderboard row is built from.
     */
    private function seedProgress(int $userId, string $scenario, int $score): void
    {
        DB::table('readiness_progress')->insert([
            'user_id' => $userId,
            'scenario' => $scenario,
            'completed' => true,
            'best_gameplay_score' => $score,
            'best_quiz_score' => $score,
            'best_overall_score' => $score,
            'stars' => 3,
            'completed_at' => now(),
        ]);
    }

    private function seedAttempt(int $userId, string $scenario, int $score): void
    {
        DB::table('readiness_attempts')->insert([
            'user_id' => $userId,
            'attempt_id' => (string) Str::uuid(),
            'scenario' => $scenario,
            'completed' => true,
            'gameplay_score' => $score,
            'quiz_score' => $score,
            'overall_score' => $score,
            'stars' => 3,
            'evidence' => '[]',
            'newly_unlocked' => '[]',
            'created_at' => now(),
        ]);
    }

    public function test_guests_are_redirected_from_the_delete_route(): void
    {
        $this->delete('/admin/leaderboard/scores', [
            'user_id' => 1,
            'scenario' => 'Typhoon',
        ])->assertRedirect(route('login'));
    }

    public function test_players_cannot_delete_leaderboard_scores(): void
    {
        $player = $this->makeUser();
        $victim = $this->makeUser();
        $this->seedProgress($victim->id, 'Typhoon', 80);

        $this->actingAs($player)
            ->delete('/admin/leaderboard/scores', [
                'user_id' => $victim->id,
                'scenario' => 'Typhoon',
            ])
            ->assertForbidden();

        $this->assertDatabaseHas('readiness_progress', ['user_id' => $victim->id, 'scenario' => 'Typhoon']);
    }

    public function test_an_administrator_can_delete_a_single_category_score(): void
    {
        $admin = $this->makeAdmin();
        $player = $this->makeUser();
        $this->seedProgress($player->id, 'Typhoon', 80);
        $this->seedAttempt($player->id, 'Typhoon', 80);

        $this->actingAs($admin)
            ->delete('/admin/leaderboard/scores', [
                'user_id' => $player->id,
                'scenario' => 'Typhoon',
                'category' => 'Typhoon',
            ])
            ->assertRedirect(route('admin.leaderboard', ['category' => 'Typhoon']))
            ->assertSessionHas('status');

        $this->assertDatabaseMissing('readiness_progress', [
            'user_id' => $player->id,
            'scenario' => 'Typhoon',
        ]);

        // Only the score record goes: the account and the attempt history stay.
        $this->assertDatabaseHas('users', ['id' => $player->id]);
        $this->assertDatabaseHas('readiness_attempts', ['user_id' => $player->id, 'scenario' => 'Typhoon']);

        $this->assertDatabaseHas('admin_activity_logs', [
            'admin_id' => $admin->id,
            'action' => 'leaderboard_score_deleted',
            'resource' => 'readiness_progress',
        ]);
    }

    public function test_the_friendly_category_name_is_resolved_before_deleting(): void
    {
        $admin = $this->makeAdmin();
        $player = $this->makeUser();
        $this->seedProgress($player->id, 'Volcano', 90);

        $this->actingAs($admin)
            ->delete('/admin/leaderboard/scores', [
                'user_id' => $player->id,
                'scenario' => 'Volcanic Eruption',
                'category' => 'Volcanic Eruption',
            ])
            ->assertRedirect(route('admin.leaderboard', ['category' => 'Volcanic Eruption']));

        $this->assertDatabaseMissing('readiness_progress', [
            'user_id' => $player->id,
            'scenario' => 'Volcano',
        ]);
    }

    public function test_a_missing_score_record_returns_404(): void
    {
        $admin = $this->makeAdmin();
        $player = $this->makeUser();

        $this->actingAs($admin)
            ->delete('/admin/leaderboard/scores', [
                'user_id' => $player->id,
                'scenario' => 'Earthquake',
            ])
            ->assertNotFound();
    }

    public function test_an_unknown_category_returns_404_and_deletes_nothing(): void
    {
        $admin = $this->makeAdmin();
        $player = $this->makeUser();
        $this->seedProgress($player->id, 'Typhoon', 70);

        $this->actingAs($admin)
            ->delete('/admin/leaderboard/scores', [
                'user_id' => $player->id,
                'scenario' => 'Atlantis',
            ])
            ->assertNotFound();

        $this->assertDatabaseHas('readiness_progress', [
            'user_id' => $player->id,
            'scenario' => 'Typhoon',
        ]);
    }

    public function test_only_the_targeted_scenario_is_removed_and_rankings_recalculate(): void
    {
        $admin = $this->makeAdmin();
        $player = $this->makeUser();
        $other = $this->makeUser();

        foreach (['Earthquake', 'Fire', 'Typhoon', 'Volcano'] as $scenario) {
            $this->seedProgress($player->id, $scenario, 80);
            $this->seedAttempt($player->id, $scenario, 80);
        }
        $this->seedProgress($other->id, 'Fire', 90);

        $this->actingAs($admin)
            ->delete('/admin/leaderboard/scores', [
                'user_id' => $player->id,
                'scenario' => 'Fire',
                'category' => 'Fire',
            ])
            ->assertSessionHas('status');

        $this->assertDatabaseMissing('readiness_progress', [
            'user_id' => $player->id,
            'scenario' => 'Fire',
        ]);

        foreach (['Earthquake', 'Typhoon', 'Volcano'] as $scenario) {
            $this->assertDatabaseHas('readiness_progress', [
                'user_id' => $player->id,
                'scenario' => $scenario,
            ]);
            $this->assertDatabaseHas('readiness_attempts', [
                'user_id' => $player->id,
                'scenario' => $scenario,
            ]);
        }

        // Every other record — including the untouched attempts — survives.
        $this->assertSame(4, DB::table('readiness_attempts')->where('user_id', $player->id)->count());
        $this->assertDatabaseHas('readiness_progress', ['user_id' => $other->id, 'scenario' => 'Fire']);

        // The category ranking is rebuilt live from the remaining records and
        // the selected category filter is preserved across the redirect.
        $response = $this->actingAs($admin)
            ->get('/admin/leaderboard?category=Fire')
            ->assertOk();

        // Assert player-cell content independently of nested presentation markup.
        $document = new \DOMDocument;
        $this->assertTrue(@$document->loadHTML($response->getContent()));
        $playerCells = (new \DOMXPath($document))->query('//tbody/tr/td[@data-label="Player"]');
        $visiblePlayers = [];
        foreach ($playerCells as $cell) {
            $visiblePlayers[] = trim($cell->textContent);
        }
        $this->assertSame([$other->username], $visiblePlayers);

        // The Overall total drops from 320 to the three surviving scenarios.
        $rows = collect(app(ReadinessService::class)->leaderboard('Overall'));
        $this->assertSame(240, $rows->firstWhere('username', $player->username)['score']);
        $this->assertSame(90, $rows->firstWhere('username', $other->username)['score']);
    }

    public function test_deletion_is_only_offered_on_a_specific_category_tab(): void
    {
        $admin = $this->makeAdmin();
        $player = $this->makeUser();
        $this->seedProgress($player->id, 'Typhoon', 80);

        // Overall rows are SUMs across scenarios, so they offer no delete.
        $this->actingAs($admin)
            ->get('/admin/leaderboard')
            ->assertOk()
            ->assertDontSee('data-delete-score');

        $this->actingAs($admin)
            ->get('/admin/leaderboard?category=Typhoon')
            ->assertOk()
            ->assertSee('data-delete-score')
            ->assertSee('score-delete-dialog');
    }
}

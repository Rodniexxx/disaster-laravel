<?php

namespace Tests\Feature;

use App\Models\Leaderboard;
use App\Services\PlayerStatsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlayerDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_landing_page_lists_scenarios(): void
    {
        $this->makeScenario(['slug' => 'fire', 'title' => 'Fire']);

        $this->get('/')->assertOk()->assertSee('Fire');
    }

    public function test_the_landing_page_shows_the_safety_guide_with_sources(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('id="safety-guide"', false)
            ->assertSee('id="emergency-info"', false)
            ->assertSee('DOST-PHIVOLCS')
            ->assertSee('DOST-PAGASA')
            ->assertSee('National Emergency Hotline')
            ->assertSee('Acknowledgment')
            ->assertSee('phivolcs.dost.gov.ph')
            ->assertSee('pagasa.dost.gov.ph')
            ->assertSee('bfp.gov.ph')
            ->assertSee('https://e911.gov.ph/');
    }

    public function test_the_public_leaderboard_is_visible_to_guests(): void
    {
        $this->makeScenario(['slug' => 'fire', 'title' => 'Fire']);

        $this->get('/leaderboard')->assertOk()->assertSee('Leaderboard');
    }

    public function test_guests_are_redirected_from_the_dashboard(): void
    {
        $this->get('/dashboard')->assertRedirect(route('login'));
    }

    public function test_a_player_can_view_the_dashboard(): void
    {
        $this->makeScenario(['slug' => 'earthquake', 'title' => 'Earthquake']);

        $this->actingAs($this->makeUser(['username' => 'dashplayer']))
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('dashplayer');
    }

    public function test_player_stats_average_the_category_bests(): void
    {
        $user = $this->makeUser();

        Leaderboard::create([
            'user_id' => $user->id, 'username' => $user->username, 'category' => 'Earthquake', 'score' => 80,
        ]);
        Leaderboard::create([
            'user_id' => $user->id, 'username' => $user->username, 'category' => 'Fire', 'score' => 60,
        ]);
        Leaderboard::create([
            'user_id' => $user->id, 'username' => $user->username, 'category' => 'Typhoon', 'score' => 70,
        ]);
        Leaderboard::create([
            'user_id' => $user->id, 'username' => $user->username, 'category' => 'Volcano', 'score' => 90,
        ]);

        $stats = app(PlayerStatsService::class)->forUser($user);

        // Average across the four fixed scenarios: (80 + 60 + 70 + 90) / 4 = 75.
        $this->assertSame(4, $stats['scenarios']->count());
        $this->assertSame(75, $stats['overall']);
        $this->assertSame(90, $stats['best']);
        $this->assertSame(1, $stats['rank']);
    }

    public function test_unplayed_scenarios_are_reported_as_not_started(): void
    {
        $user = $this->makeUser();

        Leaderboard::create([
            'user_id' => $user->id, 'username' => $user->username, 'category' => 'Earthquake', 'score' => 80,
        ]);

        $stats = app(PlayerStatsService::class)->forUser($user);

        $this->assertSame(
            ['Earthquake', 'Fire', 'Typhoon', 'Volcanic Eruption'],
            $stats['scenarios']->pluck('title')->all(),
        );
        $this->assertSame(80, $stats['perCategory']['earthquake']);
        $this->assertSame(0, $stats['perCategory']['volcano']);
        $this->assertTrue($stats['hasScore']['earthquake']);
        $this->assertFalse($stats['hasScore']['volcano']);
        // Only the played scenario counts toward the fixed four.
        $this->assertSame(20, $stats['overall']);

        $this->assertSame('Excellent', PlayerStatsService::statusLabelFor(80, true));
        $this->assertSame('Not Started', PlayerStatsService::statusLabelFor(0, false));
        // A genuine score of zero is still shown as a score, not as missing.
        $this->assertSame('Start', PlayerStatsService::statusLabelFor(0, true));
    }

    public function test_the_dashboard_lists_the_four_fixed_scenarios_with_images(): void
    {
        $this->actingAs($this->makeUser())
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Earthquake')
            ->assertSee('Fire')
            ->assertSee('Typhoon')
            ->assertSee('Volcanic Eruption')
            ->assertSee('Not Started')
            ->assertSee('images/scenarios/earthquake.webp')
            ->assertSee('images/scenarios/volcanic-eruption.webp');
    }

    public function test_adding_a_database_scenario_does_not_change_the_dashboard_cards(): void
    {
        $this->makeScenario(['slug' => 'custom', 'title' => 'Custom Disaster', 'status' => 'active']);

        $this->actingAs($this->makeUser())
            ->get('/dashboard')
            ->assertOk()
            ->assertDontSee('Custom Disaster')
            ->assertSee('Volcanic Eruption');
    }

    public function test_scores_are_isolated_between_players(): void
    {
        $a = $this->makeUser(['username' => 'alice']);
        $b = $this->makeUser(['username' => 'bob']);

        Leaderboard::create([
            'user_id' => $a->id, 'username' => 'alice', 'category' => 'Fire', 'score' => 90,
        ]);

        $stats = app(PlayerStatsService::class)->forUser($b);

        $this->assertSame(0, $stats['perCategory']['fire']);
        $this->assertFalse($stats['hasScore']['fire']);
        $this->assertSame(0, $stats['overall']);
        $this->assertNull($stats['rank']);
    }

    public function test_the_scores_and_leaderboard_show_friendly_scenario_names(): void
    {
        $user = $this->makeUser(['username' => 'volcanohero']);

        Leaderboard::create([
            'user_id' => $user->id, 'username' => 'volcanohero', 'category' => 'Volcano', 'score' => 95,
        ]);

        $this->actingAs($user)
            ->get('/scores')
            ->assertOk()
            ->assertSee('Volcanic Eruption')
            ->assertDontSee('Volcano');

        app(\App\Services\ReadinessService::class)->submit($user, [
            'attempt_id' => (string) \Illuminate\Support\Str::uuid(), 'scenario' => 'Volcano',
            'completed' => true, 'quiz_correct' => 8, 'quiz_total' => 10,
            'metrics' => array_map(fn ($id) => ['id' => $id, 'earned' => 1, 'possible' => 1],
                \App\Services\ReadinessService::CRITERIA['Volcano']),
        ]);

        $this->get('/leaderboard')
            ->assertOk()
            ->assertSee('volcanohero')
            ->assertSee('Volcanic Eruption');
    }

    public function test_inactive_players_are_logged_out_of_the_player_area(): void
    {
        $user = $this->makeUser(['is_active' => false]);

        $this->actingAs($user)->get('/dashboard')->assertRedirect(route('login'));

        $this->assertGuest();
    }
}

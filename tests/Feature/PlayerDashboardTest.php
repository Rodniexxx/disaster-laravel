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
        $this->makeScenario(['slug' => 'earthquake', 'title' => 'Earthquake']);
        $this->makeScenario(['slug' => 'fire', 'title' => 'Fire']);
        $user = $this->makeUser();

        Leaderboard::create([
            'user_id' => $user->id, 'username' => $user->username, 'category' => 'Earthquake', 'score' => 80,
        ]);
        Leaderboard::create([
            'user_id' => $user->id, 'username' => $user->username, 'category' => 'Fire', 'score' => 60,
        ]);

        $stats = app(PlayerStatsService::class)->forUser($user);

        // Average of the two active categories: (80 + 60) / 2 = 70.
        $this->assertSame(2, $stats['scenarios']->count());
        $this->assertSame(70, $stats['overall']);
        $this->assertSame(80, $stats['best']);
        $this->assertSame(1, $stats['rank']);
    }

    public function test_inactive_players_are_logged_out_of_the_player_area(): void
    {
        $user = $this->makeUser(['is_active' => false]);

        $this->actingAs($user)->get('/dashboard')->assertRedirect(route('login'));

        $this->assertGuest();
    }
}

<?php

namespace Tests\Feature;

use App\Models\Leaderboard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * The four MANGNO scenarios are fixed and cannot be edited from the admin UI.
 */
class ScenarioIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_there_is_no_admin_route_for_changing_scenarios(): void
    {
        foreach ([
            'admin.scenarios.index',
            'admin.scenarios.create',
            'admin.scenarios.store',
            'admin.scenarios.edit',
            'admin.scenarios.update',
            'admin.scenarios.destroy',
        ] as $route) {
            $this->assertFalse(Route::has($route), "Route [{$route}] must not exist.");
        }
    }

    public function test_the_landing_page_always_shows_the_four_fixed_scenarios(): void
    {
        $this->makeScenario(['slug' => 'custom', 'title' => 'Custom Disaster', 'status' => 'active']);

        $this->get('/')
            ->assertOk()
            ->assertSee('Earthquake')
            ->assertSee('Fire')
            ->assertSee('Typhoon')
            ->assertSee('Volcanic Eruption')
            ->assertDontSee('Custom Disaster');
    }

    public function test_the_leaderboard_tabs_are_the_four_fixed_scenarios(): void
    {
        $this->makeScenario(['slug' => 'custom', 'title' => 'Custom Disaster', 'status' => 'active']);

        $this->get('/leaderboard')
            ->assertOk()
            ->assertSee('Earthquake')
            ->assertSee('Fire')
            ->assertSee('Typhoon')
            ->assertSee('Volcanic Eruption')
            ->assertDontSee('Custom Disaster');
    }

    public function test_the_category_breakdown_always_lists_the_four_fixed_scenarios(): void
    {
        $this->makeScenario(['slug' => 'custom', 'title' => 'Custom Disaster', 'status' => 'active']);

        $this->actingAs($this->makeUser())
            ->get('/scores/categories')
            ->assertOk()
            ->assertSee('Earthquake')
            ->assertSee('Fire')
            ->assertSee('Typhoon')
            ->assertSee('Volcanic Eruption')
            ->assertSee('Not Started')
            ->assertDontSee('Custom Disaster');
    }

    public function test_bookmarked_and_short_category_urls_still_filter_correctly(): void
    {
        $player = $this->makeUser(['username' => 'volcanoscorer']);

        Leaderboard::create([
            'user_id' => $player->id,
            'username' => 'volcanoscorer',
            'category' => 'Volcano',
            'score' => 88,
        ]);

        foreach (['volcano', 'Volcano', 'volcanic eruption', 'Volcanic Eruption'] as $requested) {
            $this->get('/leaderboard?category='.urlencode($requested))
                ->assertOk()
                ->assertSee('volcanoscorer')
                ->assertSee('Volcanic Eruption');
        }

        $this->get('/leaderboard?category=typhoon')
            ->assertOk()
            ->assertDontSee('volcanoscorer');
    }
}

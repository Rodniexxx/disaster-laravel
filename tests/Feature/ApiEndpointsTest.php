<?php

namespace Tests\Feature;

use App\Models\Announcement;
use App\Models\Leaderboard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiEndpointsTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_endpoint_creates_a_player(): void
    {
        $this->postJson('/api/register', [
            'username' => 'unityplayer',
            'email' => 'unity@example.com',
            'password' => 'secret123',
            'confirm_password' => 'secret123',
        ])->assertOk()->assertJson([
            'success' => true,
            'username' => 'unityplayer',
        ]);

        $this->assertDatabaseHas('users', ['username' => 'unityplayer']);
    }

    public function test_register_rejects_duplicate_username(): void
    {
        $this->makeUser(['username' => 'dupe']);

        $this->postJson('/api/register', [
            'username' => 'dupe',
            'email' => 'other@example.com',
            'password' => 'secret123',
        ])->assertStatus(409)->assertJson(['success' => false]);
    }

    public function test_login_endpoint_returns_user_details(): void
    {
        $user = $this->makeUser(['username' => 'unitylogin', 'password' => 'secret123']);

        $this->postJson('/api/login', [
            'username' => 'unitylogin',
            'password' => 'secret123',
        ])->assertOk()->assertJson([
            'success' => true,
            'user_id' => $user->id,
            'role' => 'user',
        ]);
    }

    public function test_login_endpoint_rejects_bad_credentials(): void
    {
        $this->makeUser(['username' => 'unitylogin', 'password' => 'secret123']);

        $this->postJson('/api/login', [
            'username' => 'unitylogin',
            'password' => 'wrong',
        ])->assertStatus(401)->assertJson(['success' => false]);
    }

    public function test_save_score_requires_authentication(): void
    {
        $this->postJson('/api/save_score', [
            'category' => 'earthquake',
            'score' => 90,
        ])->assertStatus(401)->assertJson(['success' => false]);
    }

    public function test_authenticated_players_can_save_a_score(): void
    {
        $this->makeScenario(['slug' => 'earthquake', 'title' => 'Earthquake']);
        $user = $this->makeUser();

        $this->actingAs($user)
            ->postJson('/api/save_score', ['category' => 'earthquake', 'score' => 80])
            ->assertOk()
            ->assertJson(['success' => true, 'best_score' => 80]);

        $this->assertDatabaseHas('leaderboard', [
            'user_id' => $user->id,
            'category' => 'Earthquake',
            'score' => 80,
        ]);
    }

    public function test_saving_a_lower_score_keeps_the_best(): void
    {
        $this->makeScenario(['slug' => 'earthquake', 'title' => 'Earthquake']);
        $user = $this->makeUser();

        $this->actingAs($user)->postJson('/api/save_score', ['category' => 'earthquake', 'score' => 80]);
        $this->actingAs($user)
            ->postJson('/api/save_score', ['category' => 'earthquake', 'score' => 40])
            ->assertOk()
            ->assertJson(['best_score' => 80]);

        $this->assertSame(1, Leaderboard::query()->where('user_id', $user->id)->count());
    }

    public function test_save_score_rejects_an_unknown_category(): void
    {
        $this->actingAs($this->makeUser())
            ->postJson('/api/save_score', ['category' => 'meteor', 'score' => 50])
            ->assertStatus(400)
            ->assertJson(['success' => false]);
    }

    public function test_save_score_rejects_out_of_range_values(): void
    {
        $this->makeScenario(['slug' => 'fire', 'title' => 'Fire']);

        $this->actingAs($this->makeUser())
            ->postJson('/api/save_score', ['category' => 'fire', 'score' => 150])
            ->assertStatus(422);
    }

    public function test_get_leaderboard_returns_ranked_rows(): void
    {
        $this->makeScenario(['slug' => 'fire', 'title' => 'Fire']);
        $player = $this->makeUser(['username' => 'topdog']);

        $this->actingAs($player)->postJson('/api/save_score', ['category' => 'fire', 'score' => 70]);

        $this->getJson('/api/get_leaderboard')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('leaderboard.0.username', 'topdog')
            ->assertJsonPath('leaderboard.0.category', 'Fire')
            ->assertJsonPath('leaderboard.0.score', 70);
    }

    public function test_scenarios_endpoint_lists_active_scenarios(): void
    {
        $this->makeScenario(['slug' => 'typhoon', 'title' => 'Typhoon', 'status' => 'active']);
        $this->makeScenario(['slug' => 'hidden', 'title' => 'Hidden', 'status' => 'inactive']);

        $this->getJson('/api/scenarios')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonFragment(['slug' => 'typhoon'])
            ->assertJsonMissing(['slug' => 'hidden']);
    }

    public function test_announcements_endpoint_lists_published_announcements(): void
    {
        Announcement::create([
            'title' => 'Published news',
            'message' => 'Hello players',
            'status' => 'published',
        ]);
        Announcement::create([
            'title' => 'Draft news',
            'message' => 'Not yet',
            'status' => 'draft',
        ]);

        $this->getJson('/api/announcements')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonFragment(['title' => 'Published news'])
            ->assertJsonMissing(['title' => 'Draft news']);
    }
}

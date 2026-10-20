<?php

namespace Tests\Feature;

use App\Models\Leaderboard;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class UnityIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private function freshRequest(): void
    {
        Auth::forgetGuards();
    }

    public function test_mobile_legacy_scores_remain_available_without_entering_readiness_rankings(): void
    {
        $registration = $this->postJson('/api/register', [
            'username' => 'androidplayer', 'email' => 'android@example.com',
            'password' => 'secret123', 'password_confirmation' => 'secret123',
            'role' => 'admin', 'is_active' => false,
        ])->assertOk()->assertJsonPath('role', 'user');
        $token = $registration->json('token');
        $this->assertNotEmpty($token);
        $this->assertDatabaseMissing('personal_access_tokens', ['token' => $token]);
        $user = User::where('username', 'androidplayer')->firstOrFail();
        $this->assertTrue($user->isActive());
        foreach (['Typhoon', 'Fire', 'Earthquake', 'Volcano'] as $i => $category) {
            $this->makeScenario(['slug' => strtolower($category), 'title' => $category]);
            $this->freshRequest();
            $this->withToken($token)->postJson('/api/save_score', [
                'category' => strtolower($category), 'score' => 80 + $i, 'user_id' => 9999,
            ])->assertOk()->assertJsonPath('best_score', 80 + $i);
            $this->freshRequest();
            $this->withToken($token)->postJson('/api/save_score', [
                'category' => $category, 'score' => 10,
            ])->assertOk()->assertJsonPath('best_score', 80 + $i);
            $this->getJson('/api/get_leaderboard?category='.strtolower($category))
                ->assertOk()->assertJsonCount(1, 'leaderboard')
                ->assertJsonPath('leaderboard.0.category', $category)
                ->assertJsonPath('leaderboard.0.score', 80 + $i);
            $this->get('/leaderboard?category='.$category)
                ->assertOk()->assertViewHas('rows', fn ($rows) => $rows->isEmpty());
        }
        $this->assertSame(4, Leaderboard::where('user_id', $user->id)->count());
        // The website shows the friendly name for the Unity "Volcano" category.
        $this->actingAs($user)->get('/scores')->assertOk()->assertSee('Volcanic Eruption');
    }

    public function test_login_email_and_username_issue_tokens_and_logout_revokes_only_current_token(): void
    {
        $this->makeUser(['username' => 'mobile', 'email' => 'mobile@example.com', 'password' => 'secret123']);
        $a = $this->postJson('/api/login', ['email' => 'mobile@example.com', 'password' => 'secret123'])
            ->assertOk()->json('token');
        $b = $this->postJson('/api/login', ['username' => 'mobile', 'password' => 'secret123'])
            ->assertOk()->json('token');
        $this->freshRequest();
        $this->withToken($a)->getJson('/api/me')->assertOk()->assertJsonPath('user.username', 'mobile');
        $this->freshRequest();
        $this->withToken($a)->postJson('/api/logout')->assertOk();
        $this->freshRequest();
        $this->withToken($a)->getJson('/api/me')->assertUnauthorized();
        $this->freshRequest();
        $this->withToken($b)->getJson('/api/me')->assertOk();
    }

    public function test_disabled_expired_and_invalid_tokens_cannot_submit_even_without_accept_header(): void
    {
        $user = $this->makeUser();
        $token = $user->createToken('test', ['scores:submit'])->plainTextToken;
        $user->update(['is_active' => false]);
        $this->withToken($token)->post('/api/save_score', ['category' => 'fire', 'score' => 50])
            ->assertForbidden()->assertJsonPath('success', false);
        $user->update(['is_active' => true]);
        $expired = $user->createToken('expired', ['scores:submit'], now()->subMinute())->plainTextToken;
        $this->freshRequest();
        $this->withToken($expired)->post('/api/save_score', ['category' => 'fire', 'score' => 50])
            ->assertUnauthorized()->assertJsonPath('success', false);
        $this->freshRequest();
        $this->withToken('invalid')->post('/api/save_score', ['category' => 'fire', 'score' => 50])
            ->assertUnauthorized()->assertJsonPath('success', false);
    }

    public function test_scores_reject_decimals_arrays_and_out_of_range_values(): void
    {
        $this->makeScenario(['slug' => 'fire', 'title' => 'Fire']);
        $token = $this->makeUser()->createToken('test', ['scores:submit'])->plainTextToken;
        foreach ([1.5, -1, 101, [], 'NaN'] as $score) {
            $this->freshRequest();
            $this->withToken($token)->postJson('/api/save_score', ['category' => 'fire', 'score' => $score])
                ->assertUnprocessable()->assertJsonValidationErrors('score');
        }
        $this->assertDatabaseCount('leaderboard', 0);
    }

    public function test_registration_validation_and_disabled_login(): void
    {
        $this->postJson('/api/register', ['username' => str_repeat('x', 51), 'email' => 'bad', 'password' => '123'])
            ->assertUnprocessable()->assertJsonValidationErrors(['username', 'email', 'password']);
        $this->postJson('/api/register', ['username' => 'new', 'email' => 'new@example.com', 'password' => 'secret123', 'confirm_password' => 'different'])
            ->assertUnprocessable()->assertJsonValidationErrors('confirm_password');
        $this->makeUser(['username' => 'disabled', 'password' => 'secret123', 'is_active' => false]);
        $this->postJson('/api/login', ['username' => 'disabled', 'password' => 'secret123'])->assertUnauthorized();
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }
}

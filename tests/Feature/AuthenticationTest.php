<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_see_the_login_page(): void
    {
        $this->get('/login')->assertOk()->assertSee('Login');
    }

    public function test_a_player_can_register(): void
    {
        $response = $this->post('/register', [
            'username' => 'newplayer',
            'email' => 'newplayer@example.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ]);

        $response->assertRedirect(route('login'));
        $this->assertDatabaseHas('users', [
            'username' => 'newplayer',
            'role' => User::ROLE_USER,
        ]);
    }

    public function test_registration_rejects_mismatched_passwords(): void
    {
        $this->from('/register')->post('/register', [
            'username' => 'newplayer',
            'email' => 'newplayer@example.com',
            'password' => 'secret123',
            'password_confirmation' => 'different',
        ])->assertSessionHasErrors('password');

        $this->assertDatabaseCount('users', 0);
    }

    public function test_a_player_can_login_with_username(): void
    {
        $user = $this->makeUser(['username' => 'lucky', 'password' => 'secret123']);

        $response = $this->post('/login', [
            'login_identifier' => 'lucky',
            'password' => 'secret123',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_a_player_can_login_with_email(): void
    {
        $user = $this->makeUser(['email' => 'lucky@example.com', 'password' => 'secret123']);

        $this->post('/login', [
            'login_identifier' => 'lucky@example.com',
            'password' => 'secret123',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_an_admin_is_redirected_to_the_admin_dashboard(): void
    {
        $admin = $this->makeAdmin(['username' => 'root', 'password' => 'secret123']);

        $this->post('/login', [
            'login_identifier' => 'root',
            'password' => 'secret123',
        ])->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($admin);
    }

    public function test_an_inactive_account_cannot_login(): void
    {
        $this->makeUser([
            'username' => 'blocked',
            'password' => 'secret123',
            'is_active' => false,
        ]);

        $this->from('/login')->post('/login', [
            'login_identifier' => 'blocked',
            'password' => 'secret123',
        ])->assertSessionHasErrors('login_identifier');

        $this->assertGuest();
    }

    public function test_players_can_log_out(): void
    {
        $this->actingAs($this->makeUser())
            ->post('/logout')
            ->assertRedirect(route('home'));

        $this->assertGuest();
    }
}

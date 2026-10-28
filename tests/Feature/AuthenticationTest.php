<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
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
            'username' => 'Newplayer',
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

    public static function usernameCases(): array
    {
        return [
            ['rodnie calmada', 'Rodnie Calmada'],
            ['john michael borlagdan', 'John Michael Borlagdan'],
            ['joshua brondial', 'Joshua Brondial'],
            ['mARIA sANTOS', 'MARIA SANTOS'],
            ['rodnie  cALMADA', 'Rodnie  CALMADA'],
            ['élise pérez', 'Élise Pérez'],
            ['anna-marie o\'neill', 'Anna-marie O\'neill'],
            ['john'."\u{00A0}".'doe', 'John'."\u{00A0}".'Doe'],
        ];
    }

    #[DataProvider('usernameCases')]
    public function test_registration_capitalizes_only_the_first_letter_of_each_word(string $input, string $expected): void
    {
        $this->post('/register', [
            'username' => $input,
            'email' => 'name@example.test',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ])->assertRedirect(route('login'))->assertSessionHasNoErrors();

        $this->assertDatabaseHas('users', ['username' => $expected, 'email' => 'name@example.test']);
    }

    public function test_registration_checks_uniqueness_after_capitalization(): void
    {
        $existing = $this->makeUser(['username' => 'Rodnie Calmada']);
        $this->from('/register')->post('/register', [
            'username' => 'rodnie calmada',
            'email' => 'new@example.test',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ])->assertSessionHasErrors('username');

        $this->assertDatabaseCount('users', 1);
        $this->assertSame('Rodnie Calmada', $existing->refresh()->username);
    }

    public function test_registering_a_new_player_does_not_rename_existing_accounts(): void
    {
        $existing = $this->makeUser(['username' => 'existing lowercase']);
        $this->post('/register', [
            'username' => 'new player',
            'email' => 'new@example.test',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ])->assertRedirect(route('login'));

        $this->assertSame('existing lowercase', $existing->refresh()->username);
        $this->post('/login', ['login_identifier' => 'New Player', 'password' => 'secret123'])
            ->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs(User::where('username', 'New Player')->firstOrFail());
    }

    public function test_username_normalization_preserves_type_and_length_validation(): void
    {
        foreach ([['invalid'], str_repeat('a', 51), 'ß'.str_repeat('a', 49)] as $username) {
            $this->post('/register', [
                'username' => $username,
                'email' => 'new@example.test',
                'password' => 'secret123',
                'password_confirmation' => 'secret123',
            ])->assertSessionHasErrors('username');
        }
        $this->assertDatabaseCount('users', 0);
    }

    public function test_capitalization_is_only_enabled_on_the_registration_username_input(): void
    {
        $this->get('/register')->assertOk()->assertSee('data-registration-username', false);
        $this->get('/login')->assertOk()->assertDontSee('data-registration-username', false);
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

<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ProfileUsernameCapitalizationTest extends TestCase
{
    use RefreshDatabase;

    public static function usernameCases(): array
    {
        return [
            ['rodnie calmada', 'Rodnie Calmada'],
            ['john michael borlagdan', 'John Michael Borlagdan'],
            ['joshua brondial', 'Joshua Brondial'],
            ['mARIA sANTOS', 'MARIA SANTOS'],
            ['élise  pérez', 'Élise  Pérez'],
        ];
    }

    #[DataProvider('usernameCases')]
    public function test_profile_saves_capitalized_initials_and_preserves_other_account_data(string $input, string $expected): void
    {
        $user = $this->makeUser();
        $other = $this->makeUser(['username' => 'another lowercase']);
        $password = $user->password_hash;
        $profile = $user->playerProfile()->create(['display_name' => 'Saved display name', 'revision' => 1]);
        $originalProfile = $profile->refresh()->toArray();

        $this->actingAs($user)->from('/profile')->patch('/profile', [
            'username' => $input,
            'email' => $user->email,
        ])->assertRedirect('/profile')->assertSessionHasNoErrors();

        $this->assertSame($expected, $user->refresh()->username);
        $this->assertSame($password, $user->password_hash);
        $this->assertSame($originalProfile, $profile->refresh()->toArray());
        $this->assertSame('another lowercase', $other->refresh()->username);
    }

    public function test_profile_checks_normalized_username_uniqueness_and_allows_the_current_username(): void
    {
        $user = $this->makeUser(['username' => 'Current Name']);
        $this->makeUser(['username' => 'Rodnie Calmada']);
        $this->actingAs($user)->patch('/profile', [
            'username' => 'rodnie calmada', 'email' => $user->email,
        ])->assertSessionHasErrors('username');
        $this->assertSame('Current Name', $user->refresh()->username);

        $this->patch('/profile', [
            'username' => 'current Name', 'email' => $user->email,
        ])->assertSessionHasNoErrors();
        $this->assertSame('Current Name', $user->refresh()->username);
    }

    public function test_viewing_profile_enables_the_formatter_without_renaming_the_saved_account(): void
    {
        $user = $this->makeUser(['username' => 'existing lowercase']);
        $this->actingAs($user)->get('/profile')->assertOk()
            ->assertSee('data-profile-username', false)->assertSee('autocapitalize="words"', false);
        $this->assertSame('existing lowercase', $user->refresh()->username);
    }

    public function test_existing_profile_validation_and_authentication_still_apply(): void
    {
        $this->patch('/profile', ['username' => 'new name'])->assertRedirect(route('login'));
        $user = $this->makeUser();
        $originalName = $user->username;
        $this->actingAs($user);
        foreach ([['invalid'], str_repeat('a', 51)] as $username) {
            $this->patch('/profile', ['username' => $username, 'email' => $user->email])
                ->assertSessionHasErrors('username');
        }
        $this->patch('/profile', ['username' => 'new name', 'email' => 'invalid'])
            ->assertSessionHasErrors('email');
        $this->assertSame($originalName, $user->refresh()->username);
    }
}

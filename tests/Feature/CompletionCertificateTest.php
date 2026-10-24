<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\CompletionCertificateService;
use App\Services\ReadinessService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CompletionCertificateTest extends TestCase
{
    use RefreshDatabase;

    private const BADGES = ['ready_to_respond', 'storm_ready', 'prepared_learner', 'disaster_ready', 'mangno_master'];

    private function savedProgress(User $user, array $stars, array $badges = self::BADGES): void
    {
        $now = now();
        foreach (array_keys(ReadinessService::CRITERIA) as $index => $scenario) {
            if (! array_key_exists($index, $stars)) continue;
            DB::table('readiness_progress')->insert([
                'user_id' => $user->id, 'scenario' => $scenario, 'completed' => true,
                'best_attempt_id' => null, 'best_gameplay_score' => 80, 'best_quiz_score' => 80,
                'best_overall_score' => 80, 'stars' => $stars[$index], 'completed_at' => $now,
            ]);
        }
        foreach ($badges as $badge) {
            DB::table('readiness_achievements')->insert(['user_id' => $user->id, 'achievement_id' => $badge, 'unlocked_at' => $now]);
        }
        DB::table('player_profiles')->insert([
            'user_id' => $user->id, 'display_name' => 'Registered MANGNO Learner', 'revision' => 1,
            'created_at' => $now, 'updated_at' => $now,
        ]);
    }

    private function player(): User
    {
        return User::create(['username' => 'certificate_player', 'email' => 'certificate@example.test',
            'password_hash' => bcrypt('test-password'), 'role' => 'user', 'is_active' => true]);
    }

    public function test_certificate_is_locked_until_all_scenarios_and_all_badges_are_saved(): void
    {
        $user = $this->player();
        Sanctum::actingAs($user);
        $this->getJson('/api/readiness/certificate')->assertOk()->assertJsonPath('eligibility.eligible', false)
            ->assertJsonPath('eligibility.completed_scenarios', 0)->assertJsonPath('certificate', null);

        $this->savedProgress($user, [3]);
        $this->getJson('/api/readiness/certificate')->assertOk()->assertJsonPath('eligibility.eligible', false)
            ->assertJsonPath('eligibility.completed_scenarios', 1)->assertJsonPath('eligibility.scenarios.0.completed', true);

        DB::table('readiness_progress')->insert([
            'user_id' => $user->id, 'scenario' => 'Earthquake', 'completed' => true,
            'best_gameplay_score' => 80, 'best_quiz_score' => 80, 'best_overall_score' => 80, 'stars' => 2, 'completed_at' => now(),
        ]);
        DB::table('readiness_progress')->insert([
            'user_id' => $user->id, 'scenario' => 'Fire', 'completed' => true,
            'best_gameplay_score' => 80, 'best_quiz_score' => 80, 'best_overall_score' => 80, 'stars' => 2, 'completed_at' => now(),
        ]);
        $this->getJson('/api/readiness/certificate')->assertOk()->assertJsonPath('eligibility.eligible', false)
            ->assertJsonPath('eligibility.completed_scenarios', 3);
    }

    public function test_all_four_scenarios_without_every_badge_remain_locked(): void
    {
        $user = $this->player();
        $this->savedProgress($user, [3, 2, 3, 2], array_slice(self::BADGES, 0, 4));
        Sanctum::actingAs($user);

        $this->getJson('/api/readiness/certificate')->assertOk()->assertJsonPath('eligibility.eligible', false)
            ->assertJsonPath('eligibility.completed_scenarios', 4)->assertJsonPath('eligibility.total_stars', 10)
            ->assertJsonPath('eligibility.perfect_score', false)->assertJsonPath('certificate', null)
            ->assertJsonPath('eligibility.missing_requirements.0', 'Unlock the MANGNO Master achievement');
        $this->postJson('/api/readiness/certificate/issue')->assertForbidden();
        $this->get('/api/readiness/certificate/pdf')->assertForbidden();
        $this->assertDatabaseCount('completion_certificates', 0);
    }

    public function test_ten_star_certificate_uses_registered_name_persists_and_downloads_pdf(): void
    {
        $user = $this->player();
        $this->savedProgress($user, [3, 2, 3, 2]);
        Sanctum::actingAs($user);

        $this->getJson('/api/readiness/certificate')->assertOk()
            ->assertJsonPath('eligibility.eligible', true)->assertJsonPath('certificate', null);
        $this->assertDatabaseCount('completion_certificates', 0);

        $first = $this->postJson('/api/readiness/certificate/issue')->assertOk()
            ->assertJsonPath('certificate.eligible', true)
            ->assertJsonPath('certificate.display_name', 'Registered MANGNO Learner')
            ->assertJsonPath('certificate.total_stars', 10)
            ->assertJsonPath('certificate.perfect_score', false)
            ->assertJsonPath('certificate.issue_date', now()->format('F j, Y'));
        $id = $first->json('certificate.certificate_id');
        $this->assertNotEmpty($id);
        $this->postJson('/api/readiness/certificate/issue')->assertOk()->assertJsonPath('certificate.certificate_id', $id);
        $issuedAt = $first->json('certificate.issued_at');
        $this->app['auth']->forgetGuards();
        $this->getJson('/api/readiness/certificate')->assertUnauthorized();
        Sanctum::actingAs($user->fresh());
        $this->getJson('/api/readiness/certificate')->assertOk()->assertJsonPath('certificate.certificate_id', $id)
            ->assertJsonPath('certificate.issued_at', $issuedAt)
            ->assertJsonPath('eligibility.total_stars', 10);

        $pdf = $this->get('/api/readiness/certificate/pdf')->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-1.4', $pdf->getContent());
        $this->assertStringContainsString('10/12 STARS', $pdf->getContent());
        $this->assertStringNotContainsString('PERFECT SCORE', $pdf->getContent());
        $this->assertStringContainsString($id, $pdf->getContent());
        $this->assertStringContainsString('Registered MANGNO Learner', $pdf->getContent());
        $this->assertDatabaseCount('completion_certificates', 1);
        $this->assertDatabaseCount('readiness_progress', 4);
        $this->assertDatabaseCount('readiness_achievements', 5);
    }

    public function test_twelve_stars_displays_the_perfect_score_line_and_website_certificate(): void
    {
        $user = $this->player();
        $this->savedProgress($user, [3, 3, 3, 3]);
        Sanctum::actingAs($user);
        $this->postJson('/api/readiness/certificate/issue')->assertOk()
            ->assertJsonPath('certificate.total_stars', 12)->assertJsonPath('certificate.perfect_score', true);
        $pdf = $this->get('/api/readiness/certificate/pdf')->assertOk();
        $this->assertStringContainsString('PERFECT SCORE', $pdf->getContent());
        $this->assertStringContainsString('12/12 STARS', $pdf->getContent());

        $this->actingAs($user)->get('/dashboard')->assertOk()
            ->assertSee('CERTIFICATE OF COMPLETION')->assertSee('DOWNLOAD PDF');
        $this->get(route('dashboard.certificate'))->assertOk()->assertSee('MANGNO_Certificate_Template.jpg')
            ->assertSee('12/12 STARS')->assertSee('CERTIFICATE ID')->assertSee('Registered MANGNO Learner');
        $this->get(route('dashboard.certificate.pdf'))->assertOk()->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_client_supplied_scores_user_id_and_achievements_cannot_issue_certificate(): void
    {
        $user = $this->player();
        Sanctum::actingAs($user);
        $this->postJson('/api/readiness/certificate/issue', ['user_id' => $user->id, 'total_stars' => 12, 'achievements' => self::BADGES])->assertUnprocessable();
        $this->assertDatabaseCount('completion_certificates', 0);
        $this->getJson('/api/readiness/certificate')->assertOk()->assertJsonPath('eligibility.eligible', false);
    }

    public function test_locked_website_dashboard_lists_requirements_and_exposes_no_download(): void
    {
        $user = $this->player();
        $this->savedProgress($user, [3], []);
        $this->actingAs($user)->get('/dashboard')->assertOk()
            ->assertSee('CERTIFICATE OF COMPLETION')->assertSee('1 / 4 scenarios')
            ->assertSee('Complete the Earthquake scenario')->assertSee('Unlock the Ready to Respond achievement')
            ->assertDontSee(route('dashboard.certificate.pdf'));
    }

    public function test_certificate_endpoints_cannot_read_or_download_another_players_certificate(): void
    {
        $this->getJson('/api/readiness/certificate')->assertUnauthorized();
        $owner = $this->player();
        $this->savedProgress($owner, [3, 3, 3, 3]);
        Sanctum::actingAs($owner);
        $this->postJson('/api/readiness/certificate/issue')->assertOk();

        $other = User::create(['username' => 'other_player', 'email' => 'other@example.test',
            'password_hash' => bcrypt('test-password'), 'role' => 'user', 'is_active' => true]);
        Sanctum::actingAs($other);
        $this->getJson('/api/readiness/certificate?user_id='.$owner->id)->assertOk()
            ->assertJsonPath('eligibility.user_id', $other->id)
            ->assertJsonPath('eligibility.eligible', false)->assertJsonPath('certificate', null);
        $this->get('/api/readiness/certificate/pdf?user_id='.$owner->id)->assertForbidden();
        $this->postJson('/api/readiness/certificate/issue', ['user_id' => $owner->id])->assertUnprocessable();
        $this->assertDatabaseCount('completion_certificates', 1);
    }

    public function test_an_existing_certificate_record_does_not_bypass_current_eligibility(): void
    {
        $user = $this->player();
        $this->savedProgress($user, [3], []);
        DB::table('completion_certificates')->insert([
            'user_id' => $user->id, 'milestone' => CompletionCertificateService::MILESTONE,
            'certificate_id' => 'MANGNO-LOCKED-FIXTURE', 'issued_at' => now(),
        ]);
        Sanctum::actingAs($user);
        $this->getJson('/api/readiness/certificate')->assertOk()
            ->assertJsonPath('eligibility.eligible', false)->assertJsonPath('certificate', null);
        $this->get('/api/readiness/certificate/pdf')->assertForbidden();
        $this->actingAs($user)->get('/dashboard')->assertOk()
            ->assertSee('CERTIFICATE LOCKED')->assertDontSee(route('dashboard.certificate.pdf'));
    }
}

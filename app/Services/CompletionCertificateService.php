<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CompletionCertificateService
{
    public const MILESTONE = 'all_scenarios_all_achievements_v1';
    public const ACHIEVEMENTS = [
        'ready_to_respond' => 'Ready to Respond',
        'storm_ready' => 'Storm Ready',
        'prepared_learner' => 'Prepared Learner',
        'disaster_ready' => 'Disaster Ready',
        'mangno_master' => 'MANGNO Master',
    ];

    public function __construct(private readonly ReadinessService $readiness) {}

    /** Uses only saved readiness and achievement rows; this read never issues a certificate. */
    public function status(User $user): array
    {
        $progress = $this->readiness->progress((int) $user->id);
        $savedScenarios = collect($progress['scenarios'] ?? [])->keyBy('scenario');
        $scenarioNames = [
            'Typhoon' => 'Typhoon',
            'Earthquake' => 'Earthquake',
            'Fire' => 'Fire',
            'Volcano' => 'Volcanic Eruption',
        ];
        $scenarios = [];
        $missing = [];
        $completedCount = 0;
        $totalStars = 0;
        foreach (array_keys(ReadinessService::CRITERIA) as $scenario) {
            $row = $savedScenarios->get($scenario);
            $completed = (bool) ($row['completed'] ?? false);
            $stars = $completed ? min(3, max(0, (int) ($row['stars'] ?? 0))) : 0;
            if ($completed) {
                $completedCount++;
                $totalStars += $stars;
            } else {
                $missing[] = 'Complete the '.$scenarioNames[$scenario].' scenario';
            }
            $scenarios[] = [
                'scenario' => $scenario,
                'name' => $scenarioNames[$scenario],
                'completed' => $completed,
                'stars' => $stars,
            ];
        }

        $savedAchievements = collect($progress['achievements'] ?? [])->keyBy('id');
        $achievements = [];
        foreach (self::ACHIEVEMENTS as $id => $name) {
            $row = $savedAchievements->get($id);
            $unlocked = $row !== null;
            if (! $unlocked) $missing[] = 'Unlock the '.$name.' achievement';
            $achievements[] = [
                'id' => $id,
                'name' => $name,
                'unlocked' => $unlocked,
                'unlocked_at' => $row->unlocked_at ?? null,
            ];
        }

        $record = DB::table('completion_certificates')
            ->where('user_id', $user->id)
            ->where('milestone', self::MILESTONE)
            ->first();
        // Match the registered account name displayed in the site header; keep profile data untouched.
        $displayName = trim((string) $user->username);
        $eligible = $completedCount === count(ReadinessService::CRITERIA)
            && count(array_filter($achievements, fn (array $achievement) => $achievement['unlocked'])) === count(self::ACHIEVEMENTS);
        $stars = min(12, $totalStars);
        $issuedAt = $record ? Carbon::parse($record->issued_at) : null;

        return [
            'eligible' => $eligible,
            'user_id' => (int) $user->id,
            'display_name' => $displayName,
            'completed_scenarios' => $completedCount,
            'total_scenarios' => count(ReadinessService::CRITERIA),
            'total_stars' => $stars,
            'max_stars' => 12,
            'perfect_score' => $stars === 12,
            'scenarios' => $scenarios,
            'achievements' => $achievements,
            'missing_requirements' => $eligible ? [] : $missing,
            'certificate_id' => $record?->certificate_id,
            'issued_at' => $issuedAt?->toIso8601String(),
            'issue_date' => $issuedAt?->setTimezone(config('app.timezone'))->format('F j, Y'),
        ];
    }

    /** Idempotently creates one certificate for the authenticated player and fixed completion milestone. */
    public function issue(User $user): ?array
    {
        return DB::transaction(function () use ($user) {
            User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();
            $status = $this->status($user);
            if (! $status['eligible']) return null;

            $existing = DB::table('completion_certificates')
                ->where('user_id', $user->id)
                ->where('milestone', self::MILESTONE)
                ->first();
            if (! $existing) {
                DB::table('completion_certificates')->insertOrIgnore([
                    'user_id' => $user->id,
                    'milestone' => self::MILESTONE,
                    'certificate_id' => 'MANGNO-'.strtoupper(substr(str_replace('-', '', (string) Str::uuid()), 0, 16)),
                    'issued_at' => now(),
                ]);
            }
            return $this->status($user);
        }, 3);
    }
}

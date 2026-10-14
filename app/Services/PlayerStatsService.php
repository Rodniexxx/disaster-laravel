<?php

namespace App\Services;

use App\Models\Scenario;
use App\Models\User;
use App\Support\DisasterCatalog;
use Illuminate\Support\Collection;

/**
 * Builds the data used by the player dashboard, mirroring the calculations in
 * the legacy dashboard.php (overall = rounded average of the four category
 * bests, best = highest single category best, plus summed-score rank).
 */
class PlayerStatsService
{
    public function __construct(
        private readonly LeaderboardService $leaderboard,
        private readonly DisasterCatalog $catalog,
    ) {}

    /**
     * @return array{
     *     scenarios: Collection<int, Scenario>,
     *     perCategory: array<string,int>,
     *     overall: int,
     *     best: int,
     *     rank: int|null
     * }
     */
    public function forUser(User $user): array
    {
        $best = $this->leaderboard->bestScoresForUser($user);
        $perCategory = [];

        foreach ($this->catalog->titles() as $title) {
            $perCategory[$title] = (int) ($best[$title] ?? 0);
        }

        $values = array_values($perCategory);
        $overall = $values !== [] ? (int) round(array_sum($values) / count($values)) : 0;

        return [
            'scenarios' => $this->catalog->all(),
            'perCategory' => $perCategory,
            'overall' => $overall,
            'best' => $values !== [] ? max($values) : 0,
            'rank' => $this->leaderboard->rankForUsername($user->username),
        ];
    }

    /**
     * Readiness label per score band (matches the legacy status text).
     */
    public static function statusLabel(int $score): string
    {
        return match (true) {
            $score >= 80 => 'Excellent',
            $score >= 60 => 'Strong',
            $score >= 40 => 'Building',
            default => 'Start',
        };
    }
}

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
 *
 * The four scenarios come from the fixed {@see DisasterCatalog}, and each
 * player's score is matched to a scenario through its stored leaderboard
 * categories (canonical value plus historical aliases). A missing score is
 * distinguished from a genuine score of zero.
 */
class PlayerStatsService
{
    public function __construct(
        private readonly LeaderboardService $leaderboard,
        private readonly DisasterCatalog $catalog,
    ) {}

    /**
     * Per-scenario score rows for the dashboard and category breakdown pages.
     *
     * @return Collection<int, array{scenario:Scenario, score:int, has_score:bool}>
     */
    public function scenarioScores(User $user): Collection
    {
        $best = $this->leaderboard->bestScoresForUser($user);

        return $this->catalog->all()->map(function (Scenario $scenario) use ($best) {
            $aliasScores = [];

            foreach ($this->catalog->categoryValues($scenario->slug) as $category) {
                if (array_key_exists($category, $best)) {
                    $aliasScores[] = (int) $best[$category];
                }
            }

            return [
                'scenario' => $scenario,
                'score' => $aliasScores === [] ? 0 : max($aliasScores),
                'has_score' => $aliasScores !== [],
            ];
        });
    }

    /**
     * @return array{
     *     scenarios: Collection<int, Scenario>,
     *     perCategory: array<string,int>,
     *     hasScore: array<string,bool>,
     *     overall: int,
     *     best: int,
     *     rank: int|null
     * }
     */
    public function forUser(User $user): array
    {
        $perCategory = [];
        $hasScore = [];

        foreach ($this->scenarioScores($user) as $row) {
            $slug = $row['scenario']->slug;
            $perCategory[$slug] = $row['score'];
            $hasScore[$slug] = $row['has_score'];
        }

        $values = array_values($perCategory);
        $overall = $values !== [] ? (int) round(array_sum($values) / count($values)) : 0;

        return [
            'scenarios' => $this->catalog->all(),
            'perCategory' => $perCategory,
            'hasScore' => $hasScore,
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

    /**
     * Status shown on a scenario card. Players who have never completed a
     * scenario are labelled "Not Started" instead of the zero-score band.
     */
    public static function statusLabelFor(int $score, bool $hasScore): string
    {
        return $hasScore ? self::statusLabel($score) : 'Not Started';
    }
}

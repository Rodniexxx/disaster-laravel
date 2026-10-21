<?php

namespace App\Services;

use App\Models\Scenario;
use App\Models\User;
use App\Support\DisasterCatalog;
use Illuminate\Support\Collection;

/**
 * Builds the data used by the player dashboard and the category breakdown page.
 *
 * Every number comes from the readiness_progress records that the leaderboard
 * ranks (see {@see ReadinessService}), so the dashboard and the leaderboard can
 * never disagree about the same player. The four scenarios come from the fixed
 * {@see DisasterCatalog}, and a stored scenario name is matched to a card
 * through that catalog, so a historical spelling (for example "Volcanic
 * Eruption") never creates a second card.
 *
 * "overall" is the sum of the player's best score in every completed scenario
 * (100 per disaster, so 400 at most) and is therefore the exact number the
 * Overall leaderboard ranks. "best" is the highest individual scenario score.
 */
class PlayerStatsService
{
    /** Four disasters, each scored out of 100. */
    public const OVERALL_MAX = 400;

    public function __construct(
        private readonly ReadinessService $readiness,
        private readonly DisasterCatalog $catalog,
    ) {}

    /**
     * Per-scenario score rows for the dashboard and category breakdown pages.
     *
     * @return Collection<int, array{scenario:Scenario, score:int, has_score:bool, status:string, completed_at:mixed}>
     */
    public function scenarioScores(User $user): Collection
    {
        return $this->scoresFor($this->readiness->scenarioRecords($user->id));
    }

    /**
     * One row per fixed scenario, built from the saved readiness records.
     *
     * @param  array<string, array{scenario:string, completed:bool, score:int, gameplay_score:int, quiz_score:int, stars:int, completed_at:mixed, attempts:int}>  $records
     * @return Collection<int, array{scenario:Scenario, score:int, has_score:bool, status:string, completed_at:mixed}>
     */
    private function scoresFor(array $records): Collection
    {
        $byCategory = $this->recordsByCategory($records);

        return $this->catalog->all()->map(function (Scenario $scenario) use ($byCategory) {
            $record = $byCategory[$scenario->category] ?? null;
            $completed = (bool) ($record['completed'] ?? false);

            return [
                'scenario' => $scenario,
                'score' => $completed ? (int) ($record['score'] ?? 0) : 0,
                'has_score' => $completed,
                'status' => self::statusOf($record),
                'completed_at' => $record['completed_at'] ?? null,
            ];
        });
    }

    /**
     * @return array{
     *     scenarios: Collection<int, Scenario>,
     *     perCategory: array<string,int>,
     *     hasScore: array<string,bool>,
     *     status: array<string,string>,
     *     overall: int,
     *     overallMax: int,
     *     best: int,
     *     rank: int|null
     * }
     */
    public function forUser(User $user): array
    {
        $records = $this->readiness->scenarioRecords($user->id);
        $rows = $this->scoresFor($records);
        $summary = $this->readiness->playerSummary($user->id, $records);

        $perCategory = [];
        $hasScore = [];
        $status = [];

        foreach ($rows as $row) {
            $slug = $row['scenario']->slug;
            $perCategory[$slug] = $row['score'];
            $hasScore[$slug] = $row['has_score'];
            $status[$slug] = $row['status'];
        }

        return [
            'scenarios' => $this->catalog->all(),
            'perCategory' => $perCategory,
            'hasScore' => $hasScore,
            'status' => $status,
            'overall' => $summary['overall'],
            'overallMax' => self::OVERALL_MAX,
            'best' => $summary['best'],
            'rank' => $summary['rank'],
        ];
    }

    /**
     * The player's saved readiness records keyed by their canonical category.
     *
     * Stored spellings are resolved through the catalog, and two spellings of
     * the same disaster never add up: the best record wins.
     *
     * @param  array<string, array{scenario:string, completed:bool, score:int, gameplay_score:int, quiz_score:int, stars:int, completed_at:mixed, attempts:int}>  $records
     * @return array<string, array{scenario:string, completed:bool, score:int, gameplay_score:int, quiz_score:int, stars:int, completed_at:mixed, attempts:int}>
     */
    private function recordsByCategory(array $records): array
    {
        $byCategory = [];

        foreach ($records as $record) {
            $category = $this->catalog->resolveCategory($record['scenario']) ?? $record['scenario'];
            $current = $byCategory[$category] ?? null;

            if ($current === null
                || ($record['completed'] && ! $current['completed'])
                || ($record['completed'] === $current['completed'] && $record['score'] > $current['score'])) {
                $byCategory[$category] = $record;
            }
        }

        return $byCategory;
    }

    /**
     * The completion status of a scenario.
     *
     * "completed" only when the readiness rules recorded a completed personal
     * best, "in_progress" when the player has saved attempts for the scenario
     * but has not completed it yet, and "not_started" when the scenario was
     * never played.
     *
     * @param  array{completed?:bool, attempts?:int}|null  $record
     */
    public static function statusOf(?array $record): string
    {
        if (($record['completed'] ?? false) === true) {
            return 'completed';
        }

        return ($record['attempts'] ?? 0) > 0 ? 'in_progress' : 'not_started';
    }

    /**
     * Human readable label for a scenario status.
     */
    public static function statusLabel(string $status): string
    {
        return match ($status) {
            'completed' => 'Completed',
            'in_progress' => 'In Progress',
            default => 'Not Started',
        };
    }
}

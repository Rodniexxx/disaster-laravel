<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReadinessService
{
    public const CRITERIA = [
        'Typhoon' => ['preparation', 'family_evacuation'],
        'Earthquake' => ['drop', 'cover', 'hold'],
        'Fire' => ['objectives', 'family_evacuation', 'safe_area'],
        'Volcano' => ['mask', 'goggles', 'official_order', 'family_evacuation', 'shelter'],
    ];
    private const COUNTS = ['preparation', 'objectives', 'family_evacuation'];

    public static function overall(float $gameplay, int $quiz): int
    {
        return max(0, min(100, (int) round($gameplay * .6 + $quiz * .4)));
    }
    public static function stars(int $score, bool $completed): int
    {
        return ! $completed ? 0 : ($score >= 90 ? 3 : ($score >= 70 ? 2 : 1));
    }
    public function score(array $data): array
    {
        $criteria = self::CRITERIA[$data['scenario']];
        $metrics = collect($data['metrics'])->keyBy('id');
        if (count($data['metrics']) !== count($criteria) || $metrics->count() !== count($criteria)
            || array_diff($criteria, $metrics->keys()->all())) {
            throw ValidationException::withMessages(['metrics' => 'Supply each required gameplay criterion exactly once.']);
        }
        $sum = 0.0;
        foreach ($criteria as $id) {
            $m = $metrics[$id];
            $count = in_array($id, self::COUNTS, true) && ! ($data['scenario'] === 'Volcano');
            if ($m['earned'] > $m['possible'] || ! is_finite((float) $m['earned'])
                || ! is_finite((float) $m['possible']) || (! $count && ($m['possible'] != 1 || ! in_array((float) $m['earned'], [0.0, 1.0], true)))
                || ($count && (floor($m['earned']) != $m['earned'] || floor($m['possible']) != $m['possible']))) {
                throw ValidationException::withMessages(['metrics' => 'Invalid gameplay evidence.']);
            }
            $sum += $m['earned'] / $m['possible'];
        }
        // Completion must agree with observed critical safety evidence.
        $critical = match ($data['scenario']) {
            'Typhoon' => ['family_evacuation'],
            'Earthquake' => ['drop', 'cover', 'hold'],
            'Fire' => ['family_evacuation', 'safe_area'],
            'Volcano' => ['family_evacuation', 'official_order', 'mask', 'goggles'],
        };
        foreach ($critical as $id) {
            if ($data['completed'] && $metrics[$id]['earned'] != $metrics[$id]['possible']) {
                throw ValidationException::withMessages(['completed' => 'Completion is inconsistent with safety evidence.']);
            }
        }
        $gameplay = $sum * 100 / count($criteria);
        $quiz = $data['quiz_correct'] * 10;
        $overall = self::overall($gameplay, $quiz);
        return ['gameplay_score' => (int) round($gameplay), 'quiz_score' => $quiz,
            'overall_score' => $overall, 'stars' => self::stars($overall, (bool) $data['completed'])];
    }
    public function submit(User $user, array $data): array
    {
        $scores = $this->score($data);
        return DB::transaction(function () use ($user, $data, $scores) {
            User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            $query = DB::table('readiness_attempts')->where('user_id', $user->id)->where('attempt_id', $data['attempt_id']);
            if ($old = $query->first()) {
                if (json_decode($old->evidence, true) != $data) {
                    throw ValidationException::withMessages(['attempt_id' => 'Attempt identifier was already used for different evidence.']);
                }
                return ['progress' => $this->progress($user->id), 'newly_unlocked' => json_decode($old->newly_unlocked, true),
                    'gameplay_score' => $old->gameplay_score, 'quiz_score' => $old->quiz_score,
                    'overall_score' => $old->overall_score, 'stars' => $old->stars];
            }
            $now = now();
            DB::table('readiness_attempts')->insert(['user_id' => $user->id, 'attempt_id' => $data['attempt_id'],
                'scenario' => $data['scenario'], 'completed' => $data['completed'], ...$scores,
                'evidence' => json_encode($data), 'newly_unlocked' => '[]', 'created_at' => $now]);
            $best = DB::table('readiness_progress')->where('user_id', $user->id)->where('scenario', $data['scenario']);
            $previous = $best->first();
            // Only a successful attempt may replace the completed personal best. All
            // three component scores come from that single attempt, including ties.
            if ($data['completed'] && (! $previous || ! $previous->completed || $scores['overall_score'] > $previous->best_overall_score)) {
                DB::table('readiness_progress')->updateOrInsert(['user_id' => $user->id, 'scenario' => $data['scenario']], [
                    'completed' => true, 'best_attempt_id' => $data['attempt_id'],
                    'best_gameplay_score' => $scores['gameplay_score'], 'best_quiz_score' => $scores['quiz_score'],
                    'best_overall_score' => $scores['overall_score'], 'stars' => $scores['stars'],
                    'completed_at' => $previous?->completed_at ?? $now,
                ]);
            }
            $progress = $this->progress($user->id);
            $eligible = [];
            if ($progress['completed_scenarios'] > 0) $eligible[] = 'ready_to_respond';
            if (collect($progress['scenarios'])->contains(fn ($s) => $s['scenario'] === 'Typhoon' && $s['completed'])) $eligible[] = 'storm_ready';
            if ($data['quiz_correct'] >= 8) $eligible[] = 'prepared_learner';
            if ($progress['completed_scenarios'] === 4) $eligible[] = 'disaster_ready';
            if ($progress['total_stars'] === 12) $eligible[] = 'mangno_master';
            $fresh = [];
            foreach ($eligible as $id) {
                if (DB::table('readiness_achievements')->insertOrIgnore(['user_id' => $user->id, 'achievement_id' => $id, 'unlocked_at' => $now])) $fresh[] = $id;
            }
            $query->update(['newly_unlocked' => json_encode($fresh)]);
            return [...$scores, 'progress' => $this->progress($user->id), 'newly_unlocked' => $fresh];
        }, 3);
    }
    public function progress(int $userId): array
    {
        $saved = DB::table('readiness_progress')->where('user_id', $userId)->get()->keyBy('scenario');
        $scenarios = [];
        foreach (array_keys(self::CRITERIA) as $scenario) {
            $row = $saved->get($scenario);
            $scenarios[] = ['scenario' => $scenario, 'completed' => (bool) ($row?->completed ?? false),
                'best_gameplay_score' => (int) ($row?->best_gameplay_score ?? 0), 'best_quiz_score' => (int) ($row?->best_quiz_score ?? 0),
                'best_overall_score' => (int) ($row?->best_overall_score ?? 0), 'stars' => (int) ($row?->stars ?? 0), 'completed_at' => $row?->completed_at];
        }
        return ['scenarios' => $scenarios, 'total_stars' => array_sum(array_column($scenarios, 'stars')),
            'completed_scenarios' => count(array_filter($scenarios, fn ($s) => $s['completed'])),
            'mastered_scenarios' => count(array_filter($scenarios, fn ($s) => $s['stars'] === 3)),
            'achievements' => DB::table('readiness_achievements')->where('user_id', $userId)->orderBy('id')->get(['achievement_id as id', 'unlocked_at'])->all()];
    }
    /**
     * The player's saved readiness records, keyed by the stored scenario name.
     *
     * These are the very records the leaderboard ranks, so a player dashboard
     * built from them can never disagree with the leaderboard. The number of
     * saved attempts per scenario is included as well: it is what tells a
     * scenario that was started but not completed apart from one that was
     * never played at all.
     *
     * @return array<string, array{scenario:string, completed:bool, score:int, gameplay_score:int, quiz_score:int, stars:int, completed_at:mixed, attempts:int}>
     */
    public function scenarioRecords(int $userId): array
    {
        $attempts = DB::table('readiness_attempts')->where('user_id', $userId)
            ->select('scenario')->selectRaw('COUNT(*) as attempts')->groupBy('scenario')
            ->pluck('attempts', 'scenario');

        $records = [];
        foreach (DB::table('readiness_progress')->where('user_id', $userId)->get() as $row) {
            $records[$row->scenario] = [
                'scenario' => (string) $row->scenario,
                'completed' => (bool) $row->completed,
                'score' => (int) $row->best_overall_score,
                'gameplay_score' => (int) $row->best_gameplay_score,
                'quiz_score' => (int) $row->best_quiz_score,
                'stars' => (int) $row->stars,
                'completed_at' => $row->completed_at === null ? null : Carbon::parse($row->completed_at),
                'attempts' => (int) ($attempts[$row->scenario] ?? 0),
            ];
        }

        // A scenario that was attempted but never completed is saved progress
        // too, so it is reported as well (without a personal best).
        foreach ($attempts as $scenario => $count) {
            if (! isset($records[$scenario])) {
                $records[$scenario] = [
                    'scenario' => (string) $scenario, 'completed' => false, 'score' => 0,
                    'gameplay_score' => 0, 'quiz_score' => 0, 'stars' => 0,
                    'completed_at' => null, 'attempts' => (int) $count,
                ];
            }
        }

        return $records;
    }

    /**
     * Everything a player page needs about one player, taken from the same
     * records and the same rules the leaderboard uses.
     *
     * "overall" is the sum of the player's best score in every completed
     * scenario (100 per disaster, so 400 at most) and is therefore the exact
     * number the Overall leaderboard ranks. "best" is the highest individual
     * scenario score (100 at most). "rank" is the player's position in the
     * Overall ranking, or null while the player has no completed scenario.
     *
     * @param  array<string, array{scenario:string, completed:bool, score:int, gameplay_score:int, quiz_score:int, stars:int, completed_at:mixed, attempts:int}>|null  $records  Already fetched records, to avoid querying twice.
     * @return array{overall:int, best:int, rank:int|null, records:array<string, array{scenario:string, completed:bool, score:int, gameplay_score:int, quiz_score:int, stars:int, completed_at:mixed, attempts:int}>}
     */
    public function playerSummary(int $userId, ?array $records = null): array
    {
        $records ??= $this->scenarioRecords($userId);
        $scores = array_values(array_map(
            fn (array $record) => $record['completed'] ? $record['score'] : 0,
            $records,
        ));

        return [
            'overall' => array_sum($scores),
            'best' => $scores === [] ? 0 : max($scores),
            'rank' => $this->rankForUser($userId),
            'records' => $records,
        ];
    }

    /**
     * The player's position in the Overall leaderboard.
     *
     * The ordering is the leaderboard's own ordering (total score first, then
     * the lower user id wins the tie), so the number matches the rank shown
     * on the leaderboard page. Players without a completed scenario are not
     * ranked at all and get null.
     */
    public function rankForUser(int $userId): ?int
    {
        $mine = DB::query()->fromSub($this->totalsQuery(), 'totals')->where('id', $userId)->first();

        if (! $mine) {
            return null;
        }

        $score = (int) $mine->score;
        $ahead = DB::query()->fromSub($this->totalsQuery(), 'totals')
            ->where(function ($query) use ($score, $userId) {
                $query->where('score', '>', $score)
                    ->orWhere(function ($query) use ($score, $userId) {
                        $query->where('score', $score)->where('id', '<', $userId);
                    });
            })->count();

        return $ahead + 1;
    }

    /**
     * Grouped readiness totals for every eligible player: one row per player
     * holding the sum of their completed scenario bests. These are the exact
     * rows the leaderboard ranks.
     */
    private function totalsQuery(): \Illuminate\Database\Query\Builder
    {
        return DB::table('readiness_progress as p')->join('users as u', 'u.id', '=', 'p.user_id')
            ->where('p.completed', true)->where('u.is_active', true)
            ->groupBy('u.id', 'u.username')
            ->select('u.id as id', 'u.username as username')
            ->selectRaw('SUM(p.best_overall_score) as score');
    }

    /** Website pages are slices of the exact same top-100 ranking returned to Unity. */
    public function paginateLeaderboard(string $category, int $perPage = 20): \Illuminate\Pagination\LengthAwarePaginator
    {
        $rows = collect($this->leaderboard($category));
        $page = max(1, \Illuminate\Pagination\LengthAwarePaginator::resolveCurrentPage());
        $perPage = max(1, $perPage);

        return new \Illuminate\Pagination\LengthAwarePaginator(
            $rows->forPage($page, $perPage)->values(), $rows->count(), $perPage, $page,
            ['path' => \Illuminate\Pagination\LengthAwarePaginator::resolveCurrentPath(), 'query' => request()->query()],
        );
    }

    public function leaderboard(string $category): array
    {
        $query = $this->totalsQuery();
        if ($category !== 'Overall') $query->where('p.scenario', $category);

        return $query->orderByDesc('score')->orderBy('u.id')->limit(100)->get()->values()
            ->map(fn ($r, $i) => ['rank' => $i+1, 'username' => $r->username, 'category' => $category, 'score' => (int) $r->score])->all();
    }
}

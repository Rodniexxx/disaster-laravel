<?php

namespace App\Services;

use App\Models\PlayerProgress;
use App\Models\User;
use App\Support\DisasterCatalog;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class PlayerProgressService
{
    public function __construct(private readonly DisasterCatalog $catalog) {}

    /** Read-only aggregation: explicit completion evidence, never score/star thresholds. */
    private function totals(?int $userId = null): Builder
    {
        // Legacy and readiness tables can use different MySQL collations. Normalize
        // only this read expression; do not alter existing tables or their data.
        $identifier = fn (string $column) => DB::connection()->getDriverName() === 'mysql'
            ? "CONVERT({$column} USING utf8mb4) COLLATE utf8mb4_unicode_ci AS identifier"
            : "{$column} AS identifier";
        $legacy = DB::table('player_progress')->select('user_id')->selectRaw($identifier('scenario_slug'))
            ->selectRaw('CASE WHEN status = ? THEN 1 ELSE 0 END AS completed', ['completed'])
            ->addSelect('updated_at as activity_at')->whereIn('status', ['in_progress', 'completed']);
        $attempts = DB::table('readiness_attempts')->select('user_id')->selectRaw($identifier('scenario'))
            ->selectRaw('MAX(CASE WHEN completed = 1 THEN 1 ELSE 0 END) AS completed, MAX(created_at) AS activity_at')
            ->groupBy('user_id', 'scenario');
        $saved = DB::table('readiness_progress')->select('user_id')->selectRaw($identifier('scenario'))
            ->selectRaw('1 AS completed')->addSelect('completed_at as activity_at')->where('completed', true);
        if ($userId !== null) {
            foreach ([$legacy, $attempts, $saved] as $source) {
                $source->where('user_id', $userId);
            }
        }
        $events = $legacy->unionAll($attempts)->unionAll($saved);
        $case = 'CASE';
        $bindings = [];
        foreach ($this->catalog->definition() as $scenario) {
            $names = array_unique(array_map(
                fn ($value) => str_replace(['-', '_'], ' ', strtolower(trim($value))),
                [$scenario['slug'], $scenario['category'], $scenario['name'], ...$scenario['aliases']],
            ));
            $case .= ' WHEN LOWER(REPLACE(REPLACE(TRIM(identifier), \'-\', \' \'), \'_\', \' \')) IN ('.implode(',', array_fill(0, count($names), '?')).') THEN ?';
            $bindings = [...$bindings, ...$names, $scenario['slug']];
        }
        $case .= ' END';
        $normalized = DB::query()->fromSub($events, 'events')->select('user_id', 'completed', 'activity_at')
            ->selectRaw($case.' AS scenario_slug', $bindings);
        $scenarios = DB::query()->fromSub($normalized, 'normalized')
            ->whereNotNull('scenario_slug')->select('user_id', 'scenario_slug')
            ->selectRaw('MAX(completed) AS completed, MAX(activity_at) AS activity_at')
            ->groupBy('user_id', 'scenario_slug');
        $totals = DB::query()->fromSub($scenarios, 'scenarios')->select('user_id')
            ->selectRaw('COUNT(*) AS started_scenarios, SUM(completed) AS completed_scenarios, MAX(activity_at) AS last_activity_at')
            ->groupBy('user_id');
        foreach ($this->catalog->definition() as $index => $scenario) {
            $totals->selectRaw('MAX(CASE WHEN scenario_slug = ? THEN completed ELSE 0 END) AS scenario_'.$index, [$scenario['slug']]);
        }

        return $totals;
    }

    public function players(string $search = '', string $status = 'all', ?int $userId = null): Builder
    {
        $query = DB::table('users as u')->leftJoinSub($this->totals($userId), 'p', 'p.user_id', '=', 'u.id')
            ->where('u.role', User::ROLE_USER)->select('u.id', 'u.username', 'p.last_activity_at')
            ->selectRaw('COALESCE(p.completed_scenarios, 0) AS completed_scenarios')
            ->selectRaw('COALESCE(p.started_scenarios, 0) AS started_scenarios')
            ->when($search !== '', fn ($q) => $q->where('u.username', 'like', '%'.$search.'%'))
            ->when($userId !== null, fn ($q) => $q->where('u.id', $userId));
        foreach ($this->catalog->definition() as $index => $scenario) {
            $query->addSelect('p.scenario_'.$index);
        }
        match ($status) {
            'not_started' => $query->whereNull('p.user_id'),
            'in_progress' => $query->whereNotNull('p.user_id')->where('p.completed_scenarios', '<', 4),
            'completed' => $query->where('p.completed_scenarios', 4),
            default => null,
        };

        return $query->orderBy('u.username')->orderBy('u.id');
    }

    public function describe(object $row): array
    {
        $count = (int) $row->completed_scenarios;
        $names = [];
        foreach ($this->catalog->definition() as $index => $scenario) {
            if ((int) ($row->{'scenario_'.$index} ?? 0) === 1) {
                $names[] = $scenario['name'];
            }
        }
        $status = $count === 4 ? 'completed' : ((int) $row->started_scenarios > 0 ? 'in_progress' : 'not_started');

        return [
            'id' => (int) $row->id, 'username' => $row->username,
            'completed_scenarios' => $count, 'total_scenarios' => 4,
            'completed_names' => $names, 'progress_percentage' => $count * 25,
            'status' => $status,
            'status_label' => match ($status) { 'completed' => 'Completed', 'in_progress' => 'In Progress', default => 'Not Started' },
            'last_activity_at' => $row->last_activity_at === null ? null : Carbon::parse($row->last_activity_at)->toIso8601String(),
        ];
    }

    public function start(User $user, string $category): array
    {
        $scenario = collect($this->catalog->definition())->firstWhere('category', $category);

        return DB::transaction(function () use ($user, $scenario) {
            User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            DB::table('player_progress')->insertOrIgnore([
                'user_id' => $user->id, 'scenario_slug' => $scenario['slug'],
                'status' => PlayerProgress::STATUS_IN_PROGRESS, 'score' => 0, 'updated_at' => now(),
            ]);
            // A retry/start must never reset an existing completion or its saved score.
            DB::table('player_progress')->where('user_id', $user->id)->where('scenario_slug', $scenario['slug'])
                ->update(['status' => DB::raw("CASE WHEN status = 'completed' THEN 'completed' ELSE 'in_progress' END"), 'updated_at' => now()]);

            return $this->describe($this->players(userId: $user->id)->first());
        }, 3);
    }
}

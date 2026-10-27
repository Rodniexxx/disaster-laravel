<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\ReadinessService;
use App\Support\DisasterCatalog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LeaderboardController extends Controller
{
    public function __construct(
        private readonly ReadinessService $readiness,
        private readonly DisasterCatalog $catalog,
        private readonly AuditLogger $audit,
    ) {}

    public function index(Request $request)
    {
        $categories = array_merge(['Overall'], $this->catalog->titles());

        $requested = trim((string) $request->query('category', 'Overall'));
        $identifier = strcasecmp($requested, 'Overall') === 0
            ? 'Overall' : ($this->catalog->resolveCategory($requested) ?? 'Overall');
        $category = 'Overall';
        if (strcasecmp($requested, 'Overall') !== 0) {
            // Accepts slugs, canonical categories ("Volcano") and aliases.
            $category = $this->catalog->displayName($requested) ?? 'Overall';
        }

        $rows = $this->withDisplayCategories($this->readiness->paginateLeaderboard($identifier));

        return view('admin.leaderboard.index', [
            'categories' => $categories,
            'category' => $category,
            'rows' => $rows,
            'avatars' => app(\App\Services\AvatarService::class)->forNames($rows->getCollection()->pluck('username')->all()),
            // Row identifiers for the Delete buttons are resolved alongside the
            // ranking instead of inside it: leaderboard rows are shared with the
            // Unity API and must keep their exact public shape.
            'userIds' => User::query()
                ->whereIn('username', $rows->getCollection()->pluck('username')->all())
                ->pluck('id', 'username')
                ->all(),
        ]);
    }

    /**
     * Delete the single score record behind a category leaderboard row.
     *
     * readiness_progress is unique per (user_id, scenario), so a row on a
     * specific category tab maps to exactly one personal-best record — the
     * smallest safe scope. Overall rows are SUMs across scenarios and are
     * never offered for deletion. Accounts, attempts (history) and the other
     * scenarios' records are not touched; the ranking is recomputed live from
     * the remaining rows by ReadinessService::leaderboard().
     */
    public function destroy(Request $request)
    {
        $data = $request->validate([
            'user_id' => ['required', 'integer', 'min:1'],
            'scenario' => ['required', 'string'],
            'category' => ['nullable', 'string'],
        ]);

        $scenario = $this->catalog->resolveCategory($data['scenario']);
        abort_unless($scenario !== null, 404, 'Unknown disaster category.');

        $deleted = DB::table('readiness_progress')
            ->where('user_id', (int) $data['user_id'])
            ->where('scenario', $scenario)
            ->delete();

        abort_if($deleted === 0, 404, 'No leaderboard score exists for that player in this category.');

        $username = (string) DB::table('users')->where('id', (int) $data['user_id'])->value('username');
        $label = $this->catalog->displayName($scenario) ?? $scenario;

        $this->audit->log($request->user(), 'leaderboard_score_deleted', 'readiness_progress', [
            'user_id' => (int) $data['user_id'],
            'username' => $username,
            'scenario' => $scenario,
        ]);

        return redirect()
            ->route('admin.leaderboard', ['category' => $data['category'] ?: $label])
            ->with('status', "Deleted {$username}'s {$label} readiness score. Rankings have been recalculated.");
    }

    /**
     * Replace stored category values with their friendly scenario names.
     *
     * @param  LengthAwarePaginator<int, array{rank:int, username:string, category:string, score:int}>  $rows
     * @return LengthAwarePaginator<int, array{rank:int, username:string, category:string, score:int}>
     */
    private function withDisplayCategories(LengthAwarePaginator $rows): LengthAwarePaginator
    {
        $rows->getCollection()->transform(function (array $row) {
            $row['category'] = $this->catalog->displayName($row['category']) ?? $row['category'];

            return $row;
        });

        return $rows;
    }
}

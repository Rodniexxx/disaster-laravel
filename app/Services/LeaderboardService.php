<?php

namespace App\Services;

use App\Models\Leaderboard;
use App\Models\User;
use App\Support\DisasterCatalog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Reproduces the leaderboard behaviour of the legacy PHP site.
 */
class LeaderboardService
{
    public const LIMIT = 100;

    public function __construct(private readonly DisasterCatalog $catalog) {}

    /**
     * Ranked leaderboard rows for a category (or "Overall") as plain arrays
     * that mirror the JSON shape returned by api/get_leaderboard.php.
     *
     * The category may be a canonical category value, a display name or an
     * alias; every stored spelling that maps to the scenario is included so
     * historical records are never dropped.
     *
     * @return Collection<int, array{rank:int, username:string, category:string, score:int}>
     */
    public function rows(?string $category): Collection
    {
        return $this->query($category)
            ->ranked()
            ->limit(self::LIMIT)
            ->get()
            ->values()
            ->map(fn (Leaderboard $row, int $i) => [
                'rank' => $i + 1,
                'username' => $row->username,
                'category' => $row->category,
                'score' => (int) $row->score,
            ]);
    }

    /**
     * Paginated ranked leaderboard rows. Ranks are global (page 2 starts at
     * perPage + 1) so the ranking matches the legacy top-100 list.
     *
     * @return LengthAwarePaginator<int, array{rank:int, username:string, category:string, score:int}>
     */
    public function paginate(?string $category, int $perPage = 20): LengthAwarePaginator
    {
        $paginator = $this->query($category)
            ->ranked()
            ->paginate($perPage)
            ->withQueryString();

        $offset = ($paginator->currentPage() - 1) * $paginator->perPage();

        $paginator->getCollection()->transform(fn (Leaderboard $row, int $i) => [
            'rank' => $offset + $i + 1,
            'username' => $row->username,
            'category' => $row->category,
            'score' => (int) $row->score,
        ]);

        return $paginator;
    }

    /**
     * @return Builder<Leaderboard>
     */
    private function query(?string $category)
    {
        $query = Leaderboard::query();

        if ($category !== null && $category !== '' && $category !== 'Overall') {
            $values = $this->catalog->categoryValues($category);

            $query->whereIn('category', $values === [] ? [$category] : $values);
        }

        return $query;
    }

    /**
     * Best score per category (stored category => score) for a single player.
     * Duplicate rows for the same category are collapsed to the highest score.
     *
     * @return array<string, int>
     */
    public function bestScoresForUser(User $user): array
    {
        return Leaderboard::query()
            ->where('user_id', $user->id)
            ->get()
            ->groupBy('category')
            ->map(fn (Collection $rows) => (int) $rows->max('score'))
            ->all();
    }

    /**
     * Sum of each player's leaderboard scores, ordered high to low. Matches the
     * dashboard rank calculation in the legacy dashboard.php.
     *
     * @return array<string, int>
     */
    public function totalsByUsername(): array
    {
        $totals = Leaderboard::query()
            ->get()
            ->groupBy('username')
            ->map(fn (Collection $rows) => (int) $rows->sum('score'))
            ->all();

        arsort($totals);

        return $totals;
    }

    /**
     * 1-based rank of a player based on summed scores, or null if unranked.
     */
    public function rankForUsername(string $username): ?int
    {
        $position = 1;

        foreach (array_keys($this->totalsByUsername()) as $name) {
            if ($name === $username) {
                return $position;
            }

            $position++;
        }

        return null;
    }
}

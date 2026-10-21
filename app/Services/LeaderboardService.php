<?php

namespace App\Services;

use App\Models\Leaderboard;
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
}

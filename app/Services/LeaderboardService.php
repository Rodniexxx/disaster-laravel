<?php

namespace App\Services;

use App\Models\Leaderboard;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Reproduces the leaderboard behaviour of the legacy PHP site.
 */
class LeaderboardService
{
    public const LIMIT = 100;

    /**
     * Ranked leaderboard rows for a category (or "Overall") as plain arrays
     * that mirror the JSON shape returned by api/get_leaderboard.php.
     *
     * @return Collection<int, array{rank:int, username:string, category:string, score:int}>
     */
    public function rows(?string $category): Collection
    {
        return Leaderboard::query()
            ->forCategory($category)
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
     * Best score per category (title => score) for a single player.
     *
     * @return array<string, int>
     */
    public function bestScoresForUser(User $user): array
    {
        return Leaderboard::query()
            ->where('user_id', $user->id)
            ->get()
            ->mapWithKeys(fn (Leaderboard $row) => [$row->category => (int) $row->score])
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

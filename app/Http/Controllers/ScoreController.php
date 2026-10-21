<?php

namespace App\Http\Controllers;

use App\Models\Leaderboard;
use App\Services\PlayerStatsService;
use App\Support\DisasterCatalog;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class ScoreController extends Controller
{
    public function __construct(
        private readonly DisasterCatalog $catalog,
        private readonly PlayerStatsService $stats,
    ) {}

    /**
     * Every saved score for the signed-in player (the legacy site keeps the
     * player's best score per category; this is that list). Category labels
     * use the friendly scenario names shown elsewhere on the website.
     *
     * The readiness records (the ones the leaderboard ranks) are the source of
     * truth. A legacy quiz record is still listed for a category that has no
     * readiness record yet, so no historical score is hidden.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $scores = [];

        foreach ($this->stats->scenarioScores($user) as $row) {
            if (! $row['has_score']) {
                continue;
            }

            $scores[] = [
                'category' => $row['scenario']->title,
                'score' => (int) $row['score'],
                'created_at' => $row['completed_at'],
            ];
        }

        $covered = array_column($scores, 'category');
        $legacy = Leaderboard::query()
            ->where('user_id', $user->id)
            ->orderBy('category')
            ->get()
            ->map(fn (Leaderboard $row) => [
                'category' => $this->catalog->displayName($row->category) ?? $row->category,
                'score' => (int) $row->score,
                'created_at' => $row->created_at,
            ]);

        foreach ($legacy as $row) {
            if (! in_array($row['category'], $covered, true)) {
                $scores[] = $row;
            }
        }

        usort($scores, fn (array $a, array $b) => strcmp($a['category'], $b['category']));

        return view('player.scores.index', [
            'scores' => new Collection($scores),
        ]);
    }

    /**
     * Performance breakdown for the four built-in scenarios. Always shows all
     * four cards, even for players who have not played yet.
     */
    public function categories(Request $request)
    {
        return view('player.scores.categories', [
            'rows' => $this->stats->scenarioScores($request->user()),
        ]);
    }
}

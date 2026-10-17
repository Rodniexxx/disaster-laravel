<?php

namespace App\Http\Controllers;

use App\Models\Leaderboard;
use App\Services\PlayerStatsService;
use App\Support\DisasterCatalog;
use Illuminate\Http\Request;

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
     */
    public function index(Request $request)
    {
        $scores = Leaderboard::query()
            ->where('user_id', $request->user()->id)
            ->orderBy('category')
            ->get()
            ->map(fn (Leaderboard $row) => [
                'category' => $this->catalog->displayName($row->category) ?? $row->category,
                'score' => (int) $row->score,
                'created_at' => $row->created_at,
            ]);

        return view('player.scores.index', [
            'scores' => $scores,
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

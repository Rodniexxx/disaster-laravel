<?php

namespace App\Http\Controllers;

use App\Models\Leaderboard;
use App\Services\LeaderboardService;
use App\Support\DisasterCatalog;
use Illuminate\Http\Request;

class ScoreController extends Controller
{
    public function __construct(
        private readonly LeaderboardService $leaderboard,
        private readonly DisasterCatalog $catalog,
    ) {}

    /**
     * Every saved score for the signed-in player (the legacy site keeps the
     * player's best score per category; this is that list).
     */
    public function index(Request $request)
    {
        $scores = Leaderboard::query()
            ->where('user_id', $request->user()->id)
            ->orderBy('category')
            ->get();

        return view('player.scores.index', [
            'scores' => $scores,
        ]);
    }

    /**
     * Performance breakdown by disaster category.
     */
    public function categories(Request $request)
    {
        $best = $this->leaderboard->bestScoresForUser($request->user());

        $rows = $this->catalog->all()->map(fn ($scenario) => [
            'scenario' => $scenario,
            'score' => (int) ($best[$scenario->title] ?? 0),
        ]);

        return view('player.scores.categories', [
            'rows' => $rows,
        ]);
    }
}

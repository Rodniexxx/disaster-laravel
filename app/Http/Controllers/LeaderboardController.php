<?php

namespace App\Http\Controllers;

use App\Services\LeaderboardService;
use App\Support\DisasterCatalog;
use Illuminate\Http\Request;

class LeaderboardController extends Controller
{
    public function __construct(
        private readonly LeaderboardService $leaderboard,
        private readonly DisasterCatalog $catalog,
    ) {}

    public function index(Request $request)
    {
        $titles = $this->catalog->titles();
        $categories = array_merge(['Overall'], $titles);

        $requested = (string) $request->query('category', 'Overall');
        $category = 'Overall';
        foreach ($categories as $allowed) {
            if (strcasecmp($requested, $allowed) === 0) {
                $category = $allowed;
                break;
            }
        }

        return view('leaderboard.index', [
            'categories' => $categories,
            'category' => $category,
            'rows' => $this->leaderboard->rows($category),
        ]);
    }
}

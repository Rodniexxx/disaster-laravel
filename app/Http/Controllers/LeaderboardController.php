<?php

namespace App\Http\Controllers;

use App\Services\LeaderboardService;
use App\Support\DisasterCatalog;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class LeaderboardController extends Controller
{
    public function __construct(
        private readonly LeaderboardService $leaderboard,
        private readonly DisasterCatalog $catalog,
    ) {}

    public function index(Request $request)
    {
        $categories = array_merge(['Overall'], $this->catalog->titles());

        $requested = (string) $request->query('category', 'Overall');
        $category = 'Overall';
        if (strcasecmp($requested, 'Overall') !== 0) {
            // Accepts slugs, canonical categories ("Volcano") and aliases.
            $category = $this->catalog->displayName($requested) ?? 'Overall';
        }

        return view('leaderboard.index', [
            'categories' => $categories,
            'category' => $category,
            'rows' => $this->withDisplayCategories($this->leaderboard->rows($category)),
        ]);
    }

    /**
     * Replace stored category values with their friendly scenario names.
     *
     * @param  Collection<int, array{rank:int, username:string, category:string, score:int}>  $rows
     * @return Collection<int, array{rank:int, username:string, category:string, score:int}>
     */
    private function withDisplayCategories($rows)
    {
        return $rows->map(function (array $row) {
            $row['category'] = $this->catalog->displayName($row['category']) ?? $row['category'];

            return $row;
        });
    }
}

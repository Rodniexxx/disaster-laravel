<?php

namespace App\Http\Controllers;

use App\Services\LeaderboardService;
use App\Support\DisasterCatalog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;

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
            'rows' => $this->withDisplayCategories($this->leaderboard->paginate($category)),
        ]);
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

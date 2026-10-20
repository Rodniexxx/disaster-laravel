<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ReadinessService;
use App\Support\DisasterCatalog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;

class LeaderboardController extends Controller
{
    public function __construct(
        private readonly ReadinessService $readiness,
        private readonly DisasterCatalog $catalog,
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

        return view('admin.leaderboard.index', [
            'categories' => $categories,
            'category' => $category,
            'rows' => $this->withDisplayCategories($this->readiness->paginateLeaderboard($identifier)),
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

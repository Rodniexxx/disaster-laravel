<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\LeaderboardService;
use App\Support\DisasterCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Mirrors api/get_leaderboard.php.
 */
class LeaderboardController extends Controller
{
    public function __construct(
        private readonly LeaderboardService $leaderboard,
        private readonly DisasterCatalog $catalog,
    ) {}

    public function get(Request $request): JsonResponse
    {
        $titles = $this->catalog->titles();
        $requested = trim((string) $request->query('category', 'Overall'));
        $category = 'Overall';

        foreach ($titles as $allowed) {
            if (strcasecmp($requested, $allowed) === 0) {
                $category = $allowed;
                break;
            }
        }

        if (strcasecmp($requested, 'Overall') !== 0 && $category === 'Overall') {
            return response()->json([
                'success' => false,
                'message' => 'Invalid disaster category.',
                'leaderboard' => [],
            ], 400);
        }

        try {
            return response()->json([
                'success' => true,
                'leaderboard' => $this->leaderboard->rows($category)->all(),
            ]);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Server error while loading leaderboard.',
                'leaderboard' => [],
            ], 500);
        }
    }
}

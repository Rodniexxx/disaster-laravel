<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ScoreSubmissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Mirrors api/save_score.php. Requires an authenticated session cookie, exactly
 * like the legacy endpoint the Unity client uses.
 */
class ScoreController extends Controller
{
    public function __construct(private readonly ScoreSubmissionService $scores) {}

    public function save(Request $request): JsonResponse
    {
        $user = Auth::guard('web')->user();

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Login required.',
            ], 401);
        }

        $category = (string) $request->input('category', '');
        $scoreRaw = $request->input('score');

        if ($scoreRaw === null || $scoreRaw === '' || ! is_numeric($scoreRaw)) {
            return response()->json([
                'success' => false,
                'message' => 'Score must be a whole number from 0 to 100.',
            ], 400);
        }

        $score = (int) $scoreRaw;

        if ($score < 0 || $score > 100) {
            return response()->json([
                'success' => false,
                'message' => 'Score must be a whole number from 0 to 100.',
            ], 400);
        }

        try {
            $result = $this->scores->submit($user, $category, $score);

            if ($result === null) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid disaster category.',
                ], 400);
            }

            return response()->json([
                'success' => true,
                'message' => 'Score saved!',
                'best_score' => $result['best_score'],
            ]);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Server error while saving score.',
            ], 500);
        }
    }
}

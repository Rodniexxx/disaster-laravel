<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Scenario;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Mirrors api/scenarios.php.
 */
class ScenarioController extends Controller
{
    private const COLUMNS = [
        'id', 'slug', 'title', 'description', 'instructions',
        'learning_objectives', 'difficulty', 'status', 'safety_info', 'completion_info',
    ];

    public function index(Request $request): JsonResponse
    {
        try {
            $slug = trim((string) $request->query('scenario', ''));

            if ($slug !== '') {
                $scenario = Scenario::query()
                    ->where('slug', $slug)
                    ->where('status', 'active')
                    ->first(self::COLUMNS);

                if (! $scenario) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Scenario not found.',
                    ], 404);
                }

                return response()->json(['success' => true, 'scenario' => $scenario]);
            }

            $scenarios = Scenario::query()
                ->where('status', 'active')
                ->orderBy('title')
                ->get(self::COLUMNS);

            return response()->json(['success' => true, 'scenarios' => $scenarios]);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Server error while loading scenarios.',
            ], 500);
        }
    }
}

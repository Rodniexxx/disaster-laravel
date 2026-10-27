<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\PlayerProgressService;
use App\Support\DisasterCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ProgressController extends Controller
{
    public function start(Request $request, PlayerProgressService $progress, DisasterCatalog $catalog): JsonResponse
    {
        abort_unless($request->user()->tokenCan('scores:submit') && ! $request->user()->isAdmin(), 403);
        $data = $request->validate([
            'scenario' => ['required', 'string', 'max:120'],
            'user_id' => ['prohibited'], 'status' => ['prohibited'], 'completed' => ['prohibited'],
            'score' => ['prohibited'], 'stars' => ['prohibited'],
        ]);
        $category = $catalog->resolveCategory(str_replace(['-', '_'], ' ', $data['scenario']));
        if ($category === null) {
            throw ValidationException::withMessages(['scenario' => 'Choose Earthquake, Fire, Typhoon, or Volcano.']);
        }
        $summary = $progress->start($request->user(), $category);
        unset($summary['id'], $summary['username']);

        return response()->json(['success' => true, 'scenario' => $category, 'progress' => $summary]);
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ScoreSubmissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ScoreController extends Controller
{
    public function __construct(private readonly ScoreSubmissionService $scores) {}

    public function save(Request $request): JsonResponse
    {
        $data = $request->validate([
            'category' => ['required', 'string', 'max:120'],
            'score' => ['required', 'integer', 'min:0', 'max:100'],
        ]);
        if (! $request->user()->tokenCan('scores:submit')) {
            return response()->json(['success' => false, 'message' => 'Token cannot submit scores.'], 403);
        }
        $result = $this->scores->submit($request->user(), $data['category'], (int) $data['score']);
        if ($result === null) {
            return response()->json(['success' => false, 'message' => 'Invalid disaster category.'], 400);
        }
        return response()->json(['success' => true, 'message' => 'Score saved!', ...$result]);
    }
}

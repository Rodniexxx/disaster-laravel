<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ReadinessService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ReadinessController extends Controller
{
    public function __construct(private readonly ReadinessService $readiness) {}
    public function show(Request $request)
    {
        return response()->json(['success' => true, 'progress' => $this->readiness->progress($request->user()->id)]);
    }
    public function store(Request $request)
    {
        abort_unless($request->user()->tokenCan('scores:submit'), 403);
        $data = $request->validate([
            'attempt_id' => ['required', 'uuid'],
            'scenario' => ['required', Rule::in(array_keys(ReadinessService::CRITERIA))],
            'completed' => ['required', 'boolean'],
            'quiz_correct' => ['required', 'integer', 'between:0,10'],
            'quiz_total' => ['required', 'integer', 'in:10'],
            'metrics' => ['required', 'array', 'min:2', 'max:8'],
            'metrics.*' => ['required', 'array:id,earned,possible'],
            'metrics.*.id' => ['required', 'string', 'max:32', 'distinct'],
            'metrics.*.earned' => ['required', 'numeric', 'min:0', 'max:200'],
            'metrics.*.possible' => ['required', 'numeric', 'min:1', 'max:200'],
            'user_id' => ['prohibited'], 'stars' => ['prohibited'], 'achievements' => ['prohibited'],
            'overall_score' => ['prohibited'], 'gameplay_score' => ['prohibited'], 'quiz_score' => ['prohibited'],
        ]);
        return response()->json(['success' => true, ...$this->readiness->submit($request->user(), $data)]);
    }
    public function leaderboard(Request $request)
    {
        $data = $request->validate(['category' => ['sometimes', Rule::in(['Overall', ...array_keys(ReadinessService::CRITERIA)])]]);
        return response()->json(['success' => true, 'leaderboard' => $this->readiness->leaderboard($data['category'] ?? 'Overall')]);
    }
}

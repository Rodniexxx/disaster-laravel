<?php

namespace App\Services;

use App\Models\Leaderboard;
use App\Models\User;
use App\Support\DisasterCatalog;
use Illuminate\Support\Facades\DB;

class ScoreSubmissionService
{
    public function __construct(private readonly DisasterCatalog $catalog) {}

    public function submit(User $user, string $categoryInput, int $score): ?array
    {
        $title = $this->catalog->resolveTitle($categoryInput);
        if ($title === null) {
            return null;
        }
        return DB::transaction(function () use ($user, $title, $score) {
            // Serialize requests for this player, including the first insert.
            User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();
            $existing = Leaderboard::query()->where('user_id', $user->id)
                ->where('category', $title)->orderByDesc('score')->lockForUpdate()->first();
            if ($existing) {
                if ($score > $existing->score) {
                    $existing->score = $score;
                    $existing->created_at = now();
                }
                $existing->username = $user->username;
                $existing->save();
            } else {
                $existing = Leaderboard::create([
                    'user_id' => $user->id, 'username' => $user->username,
                    'category' => $title, 'score' => $score,
                ]);
            }
            return ['category' => $title, 'best_score' => (int) $existing->score];
        });
    }
}

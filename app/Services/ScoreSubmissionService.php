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
        $category = $this->catalog->resolveCategory($categoryInput);
        if ($category === null) {
            return null;
        }

        return DB::transaction(function () use ($user, $category, $score) {
            // Serialize requests for this player, including the first insert.
            User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();
            // Match any stored spelling of this scenario so an alias never
            // creates a duplicate category for the same disaster.
            $categories = $this->catalog->categoryValues($category);
            $existing = Leaderboard::query()->where('user_id', $user->id)
                ->whereIn('category', $categories === [] ? [$category] : $categories)
                ->orderByDesc('score')->lockForUpdate()->first();
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
                    'category' => $category, 'score' => $score,
                ]);
            }

            return ['category' => $category, 'best_score' => (int) $existing->score];
        });
    }
}

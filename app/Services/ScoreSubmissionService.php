<?php

namespace App\Services;

use App\Models\Leaderboard;
use App\Models\User;
use App\Support\DisasterCatalog;

/**
 * Preserves the score-submission behaviour of api/save_score.php.
 *
 * The legacy site keeps one best score per (player, category). Because the
 * existing leaderboard table has no unique key, we explicitly update the
 * player's existing row when it exists and only insert otherwise, which keeps
 * the intended "best score wins" semantics without altering the schema.
 */
class ScoreSubmissionService
{
    public function __construct(private readonly DisasterCatalog $catalog) {}

    /**
     * @return array{category:string,best_score:int}|null null when the category is invalid.
     */
    public function submit(User $user, string $categoryInput, int $score): ?array
    {
        $title = $this->catalog->resolveTitle($categoryInput);

        if ($title === null) {
            return null;
        }

        $existing = Leaderboard::query()
            ->where('user_id', $user->id)
            ->where('category', $title)
            ->first();

        if ($existing) {
            if ($score > $existing->score) {
                $existing->score = $score;
                $existing->username = $user->username;
                $existing->created_at = now();
            }
            $existing->username = $user->username;
            $existing->save();
            $best = (int) $existing->score;
        } else {
            $created = Leaderboard::create([
                'user_id' => $user->id,
                'username' => $user->username,
                'category' => $title,
                'score' => $score,
            ]);
            $best = (int) $created->score;
        }

        return ['category' => $title, 'best_score' => $best];
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Maps the existing disaster_game.leaderboard table.
 *
 * The legacy PHP site stores a single row per (player, disaster category) and
 * keeps the player's best score in `score`. The username is denormalised so
 * the public leaderboard can render without a join.
 */
class Leaderboard extends Model
{
    protected $table = 'leaderboard';

    /** @var list<string> */
    protected $fillable = [
        'user_id',
        'username',
        'category',
        'score',
    ];

    const CREATED_AT = 'created_at';

    const UPDATED_AT = null;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'score' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @param  Builder<Leaderboard>  $query
     * @return Builder<Leaderboard>
     */
    public function scopeForCategory(Builder $query, ?string $category): Builder
    {
        if ($category === null || $category === '' || $category === 'Overall') {
            return $query;
        }

        return $query->where('category', $category);
    }

    /**
     * @param  Builder<Leaderboard>  $query
     * @return Builder<Leaderboard>
     */
    public function scopeRanked(Builder $query): Builder
    {
        return $query->orderByDesc('score')
            ->orderBy('created_at')
            ->orderBy('username');
    }
}

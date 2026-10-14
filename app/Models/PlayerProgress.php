<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Maps disaster_game.player_progress. The unique key is (user_id, scenario_slug).
 * Only updated_at is present in the schema (no created_at).
 */
class PlayerProgress extends Model
{
    public const STATUS_NOT_STARTED = 'not_started';

    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_COMPLETED = 'completed';

    protected $table = 'player_progress';

    /** @var list<string> */
    protected $fillable = [
        'user_id',
        'scenario_slug',
        'status',
        'score',
    ];

    const CREATED_AT = null;

    const UPDATED_AT = 'updated_at';

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'score' => 'integer',
            'updated_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Maps disaster_game.game_versions.
 */
class GameVersion extends Model
{
    protected $table = 'game_versions';

    /** @var list<string> */
    protected $fillable = [
        'version_number',
        'platform',
        'status',
        'release_notes',
        'release_date',
        'download_url',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'release_date' => 'datetime',
        ];
    }

    /**
     * @param  Builder<GameVersion>  $query
     * @return Builder<GameVersion>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }
}

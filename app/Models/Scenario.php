<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Maps disaster_game.scenarios. These are the disaster "categories" surfaced
 * to players (Earthquake, Fire, Typhoon, Volcano).
 */
class Scenario extends Model
{
    protected $table = 'scenarios';

    /** @var list<string> */
    protected $fillable = [
        'slug',
        'title',
        'description',
        'instructions',
        'learning_objectives',
        'difficulty',
        'status',
        'safety_info',
        'completion_info',
    ];

    /** @return HasMany<QuizQuestion, $this> */
    public function questions(): HasMany
    {
        return $this->hasMany(QuizQuestion::class, 'scenario_id');
    }

    /**
     * @param  Builder<Scenario>  $query
     * @return Builder<Scenario>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}

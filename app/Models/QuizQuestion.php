<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Maps disaster_game.quiz_questions. Each question stores its four choices in
 * fixed columns (option_a..option_d) and the correct choice letter.
 */
class QuizQuestion extends Model
{
    public const OPTION_KEYS = ['A', 'B', 'C', 'D'];

    protected $table = 'quiz_questions';

    /** @var list<string> */
    protected $fillable = [
        'scenario_id',
        'question',
        'option_a',
        'option_b',
        'option_c',
        'option_d',
        'correct_answer',
        'explanation',
        'status',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'scenario_id' => 'integer',
        ];
    }

    /** @return BelongsTo<Scenario, $this> */
    public function scenario(): BelongsTo
    {
        return $this->belongsTo(Scenario::class);
    }

    /**
     * @param  Builder<QuizQuestion>  $query
     * @return Builder<QuizQuestion>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    /**
     * The four choices as [key => text] in display order.
     *
     * @return array<string, string>
     */
    public function choices(): array
    {
        return [
            'A' => (string) $this->option_a,
            'B' => (string) $this->option_b,
            'C' => (string) $this->option_c,
            'D' => (string) $this->option_d,
        ];
    }

    public function correctText(): ?string
    {
        return $this->choices()[strtoupper((string) $this->correct_answer)] ?? null;
    }
}

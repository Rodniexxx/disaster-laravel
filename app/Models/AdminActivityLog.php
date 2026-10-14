<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Maps disaster_game.admin_activity_logs.
 */
class AdminActivityLog extends Model
{
    protected $table = 'admin_activity_logs';

    /** @var list<string> */
    protected $fillable = [
        'admin_id',
        'action',
        'resource',
        'details',
    ];

    const CREATED_AT = 'created_at';

    const UPDATED_AT = null;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'admin_id' => 'integer',
            'details' => 'array',
            'created_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
}

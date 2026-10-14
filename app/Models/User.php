<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    public const ROLE_USER = 'user';

    public const ROLE_ADMIN = 'admin';

    /**
     * The existing disaster_game.users table stores the hash in password_hash
     * and only tracks created_at (there is no updated_at column).
     *
     * @var list<string>
     */
    protected $fillable = [
        'username',
        'email',
        'password_hash',
        'role',
        'is_active',
        'last_login_at',
    ];

    /** @var list<string> */
    protected $hidden = [
        'password_hash',
    ];

    const CREATED_AT = 'created_at';

    const UPDATED_AT = null;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    /**
     * Laravel's session guard reads the password through this accessor.
     */
    public function getAuthPassword(): string
    {
        return (string) $this->password_hash;
    }

    /* -----------------------------------------------------------------
     | Relationships
     | ----------------------------------------------------------------- */

    /** @return HasMany<Leaderboard, $this> */
    public function scores(): HasMany
    {
        return $this->hasMany(Leaderboard::class);
    }

    /** @return HasMany<PlayerProgress, $this> */
    public function progress(): HasMany
    {
        return $this->hasMany(PlayerProgress::class);
    }

    /* -----------------------------------------------------------------
     | Helpers
     | ----------------------------------------------------------------- */

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isActive(): bool
    {
        return (bool) $this->is_active;
    }

    public function getInitialsAttribute(): string
    {
        $name = trim((string) $this->username);
        $parts = preg_split('/[\s._-]+/', $name) ?: [];

        return strtoupper(mb_substr($parts[0] ?? '', 0, 1).mb_substr($parts[1] ?? '', 0, 1));
    }
}

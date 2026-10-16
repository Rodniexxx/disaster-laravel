<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/**
 * Maps disaster_game.game_versions.
 *
 * Each row is a downloadable game build for one platform. The web admin uses
 * this table as the single source of truth for the public /download page.
 */
class GameVersion extends Model
{
    public const STATUS_PUBLISHED = 'active';

    public const STATUS_UNPUBLISHED = 'inactive';

    protected $table = 'game_versions';

    /** @var list<string> */
    protected $fillable = [
        'version_number',
        'release_title',
        'platform',
        'status',
        'release_notes',
        'release_date',
        'download_url',
        'file_path',
        'file_name',
        'file_size',
        'requirements',
        'is_latest',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'release_date' => 'datetime',
            'is_latest' => 'boolean',
            'file_size' => 'integer',
        ];
    }

    /**
     * @param  Builder<GameVersion>  $query
     * @return Builder<GameVersion>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PUBLISHED);
    }

    /**
     * Case-insensitive platform match, so legacy rows stored as "Windows"
     * still match the "windows" key used by the admin and public pages.
     *
     * @param  Builder<GameVersion>  $query
     * @return Builder<GameVersion>
     */
    public function scopePlatform(Builder $query, string $platform): Builder
    {
        return $query->whereRaw('LOWER(platform) = ?', [strtolower($platform)]);
    }

    /**
     * Rows that claim a download, either an uploaded file or an external URL.
     *
     * @param  Builder<GameVersion>  $query
     * @return Builder<GameVersion>
     */
    public function scopeWithDownload(Builder $query): Builder
    {
        return $query->where(function (Builder $query) {
            $query->whereNotNull('file_path')->orWhereNotNull('download_url');
        });
    }

    /** True when an uploaded file exists on disk right now. */
    public function hasLocalFile(): bool
    {
        return $this->file_path !== null
            && Storage::disk(config('mangno.downloads.disk', 'local'))->exists($this->file_path);
    }

    /** True when this release can actually be downloaded. */
    public function isAvailable(): bool
    {
        return $this->hasLocalFile() || $this->download_url !== null;
    }

    public function isPublished(): bool
    {
        return $this->status === self::STATUS_PUBLISHED;
    }

    /** File extension of the downloadable artifact, without the dot. */
    public function extension(): ?string
    {
        $path = $this->hasLocalFile() ? $this->file_path : $this->download_url;

        if (! $path) {
            return null;
        }

        $extension = strtolower(pathinfo(parse_url($path, PHP_URL_PATH) ?: $path, PATHINFO_EXTENSION));

        return $extension !== '' ? $extension : null;
    }

    public function formattedSize(): ?string
    {
        return self::formatBytes($this->file_size);
    }

    public static function formatBytes(?int $bytes): ?string
    {
        if ($bytes === null || $bytes <= 0) {
            return null;
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $power = min((int) floor(log($bytes, 1024)), count($units) - 1);

        return round($bytes / (1024 ** $power), $power === 0 ? 0 : 1).' '.$units[$power];
    }
}

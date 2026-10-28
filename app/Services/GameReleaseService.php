<?php

namespace App\Services;

use App\Models\GameVersion;

/** Selects published game releases with external download URLs. */
class GameReleaseService
{
    /**
     * The release the public page should offer for a platform: the newest
     * published release that has a usable download, preferring one explicitly
     * flagged as latest.
     */
    public function latest(string $platform): ?GameVersion
    {
        return GameVersion::query()
            ->active()
            ->platform($platform)
            ->withDownload()
            ->orderByDesc('is_latest')
            ->orderByDesc('release_date')
            ->orderByDesc('id')
            ->get()
            ->first(fn (GameVersion $release) => $release->isAvailable());
    }
}

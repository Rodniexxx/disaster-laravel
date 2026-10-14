<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;

/**
 * Persists the admin "Settings" page. The legacy PHP page silently discarded
 * its input; here the values are stored as JSON in storage/app so the feature
 * actually works without adding tables to the existing database.
 */
class SettingsService
{
    private const PATH = 'settings.json';

    /** @return array{site_name:string,site_tagline:string} */
    public function all(): array
    {
        $defaults = [
            'site_name' => config('app.name', 'MANGNO'),
            'site_tagline' => 'Preparedness Simulation',
        ];

        if (! Storage::disk('local')->exists(self::PATH)) {
            return $defaults;
        }

        $stored = json_decode((string) Storage::disk('local')->get(self::PATH), true);

        return array_merge($defaults, is_array($stored) ? $stored : []);
    }

    /** @param array<string, mixed> $values */
    public function put(array $values): void
    {
        Storage::disk('local')->put(
            self::PATH,
            json_encode(array_merge($this->all(), $values), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );
    }
}

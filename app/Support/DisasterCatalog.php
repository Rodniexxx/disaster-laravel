<?php

namespace App\Support;

use App\Models\Scenario;
use Illuminate\Support\Collection;

/**
 * The single source of truth for MANGNO's four permanent disaster scenarios.
 *
 * The list is defined statically in config/mangno.php so it is identical for
 * every player regardless of what the `scenarios` database table contains.
 * Administrators cannot add, rename, reorder or delete these four entries.
 *
 * The `scenarios` table is still used by the Unity integration (scenario
 * details, quiz questions) and is intentionally left in place.
 *
 * Categories are identified in the `leaderboard` table by the value configured
 * under "category" (for example "Volcano"), while the website displays the
 * friendlier "name" (for example "Volcanic Eruption"). "aliases" keeps every
 * historical spelling mapped to the same scenario so no score record is lost
 * and no duplicate category is created.
 */
class DisasterCatalog
{
    /**
     * Raw scenario definition from configuration.
     *
     * @return list<array{slug:string,name:string,image:string,description:string,difficulty:string,category:string,aliases:list<string>}>
     */
    public function definition(): array
    {
        /** @var list<array<string, mixed>> $scenarios */
        $scenarios = config('mangno.scenarios', []);

        return $scenarios;
    }

    /**
     * The four built-in scenarios in their fixed display order.
     *
     * @return Collection<int, Scenario>
     */
    public function all(): Collection
    {
        return collect($this->definition())
            ->map(fn (array $scenario) => $this->toScenario($scenario))
            ->values();
    }

    /**
     * Display names for the four scenarios, in fixed order.
     *
     * @return list<string>
     */
    public function titles(): array
    {
        return array_map(fn (array $scenario) => $scenario['name'], $this->definition());
    }

    /**
     * Map of lowercased slug => display name.
     *
     * @return array<string, string>
     */
    public function slugToTitle(): array
    {
        return collect($this->definition())
            ->mapWithKeys(fn (array $scenario) => [strtolower($scenario['slug']) => $scenario['name']])
            ->all();
    }

    /**
     * Resolve a user-supplied category string (slug, name, canonical category
     * or alias, in any case) to the canonical value stored in the leaderboard
     * table. Returns null when nothing matches.
     */
    public function resolveCategory(?string $input): ?string
    {
        $needle = strtolower(trim((string) $input));

        if ($needle === '') {
            return null;
        }

        foreach ($this->definition() as $scenario) {
            foreach ($this->identifiers($scenario) as $identifier) {
                if ($identifier === $needle) {
                    return $scenario['category'];
                }
            }
        }

        return null;
    }

    /**
     * Backwards-compatible alias for {@see resolveCategory()}. Returns the
     * canonical leaderboard category, not the display name.
     */
    public function resolveTitle(?string $input): ?string
    {
        return $this->resolveCategory($input);
    }

    /**
     * Every stored leaderboard category value that belongs to a scenario
     * (canonical first, then aliases). Used when filtering scores so that
     * historical spellings keep working. Returns [] for unknown input.
     *
     * @return list<string>
     */
    public function categoryValues(string $identifier): array
    {
        $needle = strtolower(trim($identifier));

        foreach ($this->definition() as $scenario) {
            if (in_array($needle, $this->identifiers($scenario), true)) {
                return array_values(array_unique(array_merge(
                    [$scenario['category']],
                    $scenario['aliases'],
                )));
            }
        }

        return [];
    }

    /**
     * Every canonical category value for the four scenarios.
     *
     * @return list<string>
     */
    public function categories(): array
    {
        return array_map(fn (array $scenario) => $scenario['category'], $this->definition());
    }

    /**
     * Friendly display name for a stored leaderboard category value, or null
     * when the value does not belong to one of the built-in scenarios.
     */
    public function displayName(?string $storedCategory): ?string
    {
        $needle = strtolower(trim((string) $storedCategory));

        if ($needle === '') {
            return null;
        }

        foreach ($this->definition() as $scenario) {
            if (in_array($needle, $this->identifiers($scenario), true)) {
                return $scenario['name'];
            }
        }

        return null;
    }

    public function findBySlug(string $slug): ?Scenario
    {
        $needle = strtolower(trim($slug));

        foreach ($this->definition() as $scenario) {
            if (strtolower($scenario['slug']) === $needle) {
                return $this->toScenario($scenario);
            }
        }

        return null;
    }

    /**
     * All lowercase identifiers (slug, name, category, aliases) for a scenario.
     *
     * @param  array<string, mixed>  $scenario
     * @return list<string>
     */
    private function identifiers(array $scenario): array
    {
        $values = array_merge(
            [$scenario['slug'], $scenario['name'], $scenario['category']],
            $scenario['aliases'] ?? [],
        );

        return array_values(array_unique(array_map('strtolower', $values)));
    }

    /**
     * Build an in-memory (never persisted) Scenario model from configuration.
     *
     * @param  array<string, mixed>  $scenario
     */
    private function toScenario(array $scenario): Scenario
    {
        $model = new Scenario([
            'slug' => $scenario['slug'],
            'title' => $scenario['name'],
            'description' => $scenario['description'] ?? '',
            'difficulty' => $scenario['difficulty'] ?? null,
            'status' => 'active',
        ]);

        // Extra, non-persisted attributes used by the views.
        $model->setAttribute('image', $scenario['image'] ?? null);
        $model->setAttribute('category', $scenario['category'] ?? null);

        return $model;
    }
}

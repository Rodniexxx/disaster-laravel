<?php

namespace App\Support;

use App\Models\Scenario;
use Illuminate\Support\Collection;

/**
 * Resolves disaster categories from the live scenarios table so that whatever
 * an administrator has configured is what both the website and the Unity API
 * expose. Categories are identified in the database by their slug but are
 * displayed (and stored on the leaderboard) using the scenario title.
 */
class DisasterCatalog
{
    /** @return Collection<int, Scenario> */
    public function all(): Collection
    {
        return Scenario::query()->active()->orderBy('title')->get();
    }

    /**
     * Map of lowercased slug => title for the active scenarios.
     *
     * @return array<string, string>
     */
    public function slugToTitle(): array
    {
        return $this->all()
            ->mapWithKeys(fn (Scenario $s) => [strtolower($s->slug) => $s->title])
            ->all();
    }

    /**
     * All active scenario titles.
     *
     * @return list<string>
     */
    public function titles(): array
    {
        return $this->all()->pluck('title')->all();
    }

    /**
     * Resolve a user-supplied category string (slug or title, any case) to the
     * canonical scenario title. Returns null when it does not match.
     */
    public function resolveTitle(?string $input): ?string
    {
        $needle = strtolower(trim((string) $input));

        if ($needle === '') {
            return null;
        }

        foreach ($this->all() as $scenario) {
            if (strtolower($scenario->slug) === $needle || strtolower($scenario->title) === $needle) {
                return $scenario->title;
            }
        }

        return null;
    }

    public function findBySlug(string $slug): ?Scenario
    {
        return Scenario::query()->where('slug', $slug)->first();
    }
}

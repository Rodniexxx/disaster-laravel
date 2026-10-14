<?php

namespace Tests\Unit;

use App\Support\DisasterCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DisasterCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_resolves_slugs_and_titles_case_insensitively(): void
    {
        $this->makeScenario(['slug' => 'earthquake', 'title' => 'Earthquake']);

        $catalog = app(DisasterCatalog::class);

        $this->assertSame('Earthquake', $catalog->resolveTitle('earthquake'));
        $this->assertSame('Earthquake', $catalog->resolveTitle('EARTHQUAKE'));
        $this->assertSame('Earthquake', $catalog->resolveTitle('Earthquake'));
    }

    public function test_unknown_categories_resolve_to_null(): void
    {
        $this->makeScenario(['slug' => 'earthquake', 'title' => 'Earthquake']);

        $this->assertNull(app(DisasterCatalog::class)->resolveTitle('meteor'));
        $this->assertNull(app(DisasterCatalog::class)->resolveTitle(''));
    }

    public function test_it_only_lists_active_scenarios(): void
    {
        $this->makeScenario(['slug' => 'fire', 'title' => 'Fire', 'status' => 'active']);
        $this->makeScenario(['slug' => 'retired', 'title' => 'Retired', 'status' => 'inactive']);

        $this->assertSame(['Fire'], app(DisasterCatalog::class)->titles());
    }
}

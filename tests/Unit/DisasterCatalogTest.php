<?php

namespace Tests\Unit;

use App\Support\DisasterCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DisasterCatalogTest extends TestCase
{
    use RefreshDatabase;

    private function catalog(): DisasterCatalog
    {
        return app(DisasterCatalog::class);
    }

    public function test_it_resolves_slugs_and_aliases_case_insensitively(): void
    {
        $catalog = $this->catalog();

        $this->assertSame('Earthquake', $catalog->resolveCategory('earthquake'));
        $this->assertSame('Earthquake', $catalog->resolveCategory('EARTHQUAKE'));
        $this->assertSame('Earthquake', $catalog->resolveCategory('Earthquake'));
        $this->assertSame('Fire', $catalog->resolveCategory('fire'));
        $this->assertSame('Volcano', $catalog->resolveCategory('volcano'));
        $this->assertSame('Volcano', $catalog->resolveCategory('Volcanic Eruption'));
        $this->assertSame('Volcano', $catalog->resolveCategory('volcanic eruption'));
    }

    public function test_unknown_categories_resolve_to_null(): void
    {
        $catalog = $this->catalog();

        $this->assertNull($catalog->resolveCategory('meteor'));
        $this->assertNull($catalog->resolveCategory(''));
        $this->assertNull($catalog->resolveCategory(null));
    }

    public function test_resolve_title_remains_backwards_compatible(): void
    {
        $catalog = $this->catalog();

        $this->assertSame('Earthquake', $catalog->resolveTitle('earthquake'));
        $this->assertSame('Volcano', $catalog->resolveTitle('Volcanic Eruption'));
        $this->assertNull($catalog->resolveTitle('meteor'));
    }

    public function test_the_four_built_in_scenarios_are_listed_without_any_database_rows(): void
    {
        $this->assertSame(
            ['Earthquake', 'Fire', 'Typhoon', 'Volcanic Eruption'],
            $this->catalog()->titles(),
        );
    }

    public function test_database_rows_cannot_change_the_built_in_scenario_list(): void
    {
        $this->makeScenario(['slug' => 'retired', 'title' => 'Retired', 'status' => 'inactive']);
        $this->makeScenario(['slug' => 'custom', 'title' => 'Custom Disaster', 'status' => 'active']);

        $catalog = $this->catalog();

        $this->assertSame(
            ['Earthquake', 'Fire', 'Typhoon', 'Volcanic Eruption'],
            $catalog->titles(),
        );
        $this->assertNull($catalog->resolveCategory('custom'));
        $this->assertNull($catalog->displayName('Custom Disaster'));
    }

    public function test_every_historical_category_spelling_maps_to_one_scenario(): void
    {
        $catalog = $this->catalog();

        $this->assertSame(['Volcano', 'Volcanic Eruption'], $catalog->categoryValues('volcano'));
        $this->assertSame(['Volcano', 'Volcanic Eruption'], $catalog->categoryValues('Volcanic Eruption'));
        $this->assertSame(['Earthquake'], $catalog->categoryValues('earthquake'));
        $this->assertSame([], $catalog->categoryValues('meteor'));

        $this->assertSame('Volcanic Eruption', $catalog->displayName('Volcano'));
        $this->assertSame('Volcanic Eruption', $catalog->displayName('volcanic eruption'));
        $this->assertSame('Earthquake', $catalog->displayName('Earthquake'));
        $this->assertNull($catalog->displayName('meteor'));
    }

    public function test_it_reports_the_canonical_leaderboard_categories(): void
    {
        $this->assertSame(
            ['Earthquake', 'Fire', 'Typhoon', 'Volcano'],
            $this->catalog()->categories(),
        );
    }

    public function test_it_builds_scenario_models_with_images_from_configuration(): void
    {
        $scenarios = $this->catalog()->all();

        $this->assertCount(4, $scenarios);
        $this->assertSame('earthquake', $scenarios->first()->slug);
        $this->assertSame('images/scenarios/earthquake.webp', $scenarios->first()->image);
        $this->assertSame(
            'images/scenarios/volcano.webp',
            $scenarios->firstWhere('slug', 'volcano')->image,
        );

        foreach ($scenarios as $scenario) {
            $this->assertFileExists(public_path($scenario->image));
            $this->assertTrue($scenario->exists === false, 'Scenario must not be persisted.');
        }
    }
}

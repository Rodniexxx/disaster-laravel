<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ListPaginationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_pages_are_limited_to_ten_and_keep_filters_and_stable_order(): void
    {
        $admin = $this->makeAdmin();
        $expectedIds = [];
        for ($i = 0; $i < 23; $i++) {
            $expectedIds[] = $this->makeUser([
                'username' => 'paging-member-'.$i,
                'created_at' => '2026-10-01 12:00:00',
            ])->id;
        }
        $this->makeUser(['username' => 'paging-member-inactive', 'is_active' => false]);
        $seen = [];
        foreach ([1 => 10, 2 => 10, 3 => 3] as $page => $count) {
            $response = $this->actingAs($admin)
                ->get('/admin/users?search=paging-member&status=active&page='.$page)
                ->assertOk();
            $users = $response->viewData('users');
            $this->assertSame(10, $users->perPage());
            $this->assertSame(23, $users->total());
            $this->assertCount($count, $users);
            $seen = [...$seen, ...$users->pluck('id')->all()];
            $response->assertSee('Showing')->assertSee('aria-label="Pagination"', false)
                ->assertSee('aria-current="page"', false);
            if ($page < 3) {
                $this->assertStringContainsString('search=paging-member', $users->nextPageUrl());
                $this->assertStringContainsString('status=active', $users->nextPageUrl());
            }
        }
        $this->assertSame(array_reverse($expectedIds), $seen);
    }

    public function test_empty_user_filter_keeps_an_honest_pagination_summary(): void
    {
        $this->actingAs($this->makeAdmin())
            ->get('/admin/users?search=does-not-exist')->assertOk()
            ->assertSee('No users found.')->assertSee('No results')
            ->assertDontSee('aria-current="page"', false);
    }
}

<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_from_the_admin_area(): void
    {
        $this->get('/admin')->assertRedirect(route('login'));
    }

    public function test_players_are_forbidden_from_the_admin_area(): void
    {
        $this->actingAs($this->makeUser())
            ->get('/admin')
            ->assertForbidden();
    }

    public function test_administrators_can_access_the_dashboard(): void
    {
        $this->actingAs($this->makeAdmin())
            ->get('/admin')
            ->assertOk()
            ->assertSee('Admin');
    }

    public function test_administrators_can_open_management_pages(): void
    {
        $admin = $this->makeAdmin();

        foreach ([
            '/admin/users',
            '/admin/questions',
            '/admin/progress',
            '/admin/announcements',
            '/admin/versions',
            '/admin/logs',
            '/admin/leaderboard',
            '/admin/settings',
        ] as $uri) {
            $this->actingAs($admin)->get($uri)->assertOk();
        }
    }

    public function test_an_administrator_can_create_an_announcement(): void
    {
        $this->actingAs($this->makeAdmin())
            ->post('/admin/announcements', [
                'title' => 'System update',
                'message' => 'New scenarios are live.',
                'status' => 'published',
            ])
            ->assertRedirect(route('admin.announcements.index'));

        $this->assertDatabaseHas('announcements', ['title' => 'System update']);
    }
}

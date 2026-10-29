<?php

namespace App\Services;

use App\Models\AdminActivityLog;
use App\Models\Announcement;
use App\Models\GameVersion;
use App\Models\Leaderboard;
use App\Models\QuizQuestion;
use App\Models\Scenario;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

/**
 * Aggregates the numbers shown on the admin dashboard.
 */
class AdminStatsService
{
    public function __construct(private readonly PlayerProgressService $progress) {}

    /**
     * @return array<string, int>
     */
    public function totals(): array
    {
        return [
            'players' => User::query()->count(),
            'active_players' => User::query()->where('is_active', true)->count(),
            'admins' => User::query()->where('role', User::ROLE_ADMIN)->count(),
            'scenarios' => Scenario::query()->count(),
            'questions' => QuizQuestion::query()->count(),
            'sessions' => Leaderboard::query()->count(),
            'completed_players' => $this->progress->players(status: 'completed')->count(),
            'announcements' => Announcement::query()->count(),
            'versions' => GameVersion::query()->count(),
        ];
    }

    /** @return Collection<int, User> */
    public function recentPlayers(int $limit = 5)
    {
        return User::query()->orderByDesc('created_at')->limit($limit)->get();
    }

    /** @return Collection<int, Leaderboard> */
    public function recentScores(int $limit = 5)
    {
        return Leaderboard::query()->orderByDesc('created_at')->limit($limit)->get();
    }

    /** @return Collection<int, AdminActivityLog> */
    public function recentLogs(int $limit = 5)
    {
        return AdminActivityLog::query()->with('admin')->orderByDesc('created_at')->limit($limit)->get();
    }

    public function currentVersion(): ?GameVersion
    {
        return GameVersion::query()->active()->orderByDesc('release_date')->orderByDesc('created_at')->first();
    }

    public function latestAnnouncement(): ?Announcement
    {
        return Announcement::query()->published()->orderByDesc('publish_date')->first();
    }
}

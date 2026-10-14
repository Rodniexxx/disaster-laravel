<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Services\AdminStatsService;

class DashboardController extends Controller
{
    public function __invoke(AdminStatsService $stats)
    {
        return view('admin.dashboard', [
            'totals' => $stats->totals(),
            'recentPlayers' => $stats->recentPlayers(),
            'recentScores' => $stats->recentScores(),
            'recentLogs' => $stats->recentLogs(),
            'currentVersion' => $stats->currentVersion(),
            'latestAnnouncement' => $stats->latestAnnouncement(),
            'recentAnnouncements' => Announcement::query()->orderByDesc('publish_date')->limit(5)->get(),
        ]);
    }
}

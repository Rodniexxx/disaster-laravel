@extends('layouts.admin')

@section('title', 'Admin Dashboard')
@section('heading', 'Dashboard')
@section('eyebrow', 'Overview')

@section('content')
    <section class="stats-strip admin-stats">
        <article class="stat-panel highlight">
            <span class="stat-label">Total Players</span>
            <strong class="stat-value">{{ number_format($totals['players']) }}</strong>
            <small>{{ number_format($totals['active_players']) }} active accounts</small>
        </article>
        <article class="stat-panel highlight">
            <span class="stat-label">Game Sessions</span>
            <strong class="stat-value">{{ number_format($totals['sessions']) }}</strong>
            <small>Saved score entries</small>
        </article>
        <article class="stat-panel highlight">
            <span class="stat-label">Completed Scenarios</span>
            <strong class="stat-value">{{ number_format($totals['completed_progress']) }}</strong>
            <small>Completed player progress records</small>
        </article>
        <a href="{{ route('admin.questions.index') }}" class="stat-card-link {{ request()->routeIs('admin.questions.*') ? 'active' : '' }}">
    <article class="stat-panel highlight">
        <span class="stat-label">Scenarios</span>
        <strong class="stat-value">{{ number_format($totals['scenarios']) }}</strong>
        <small>{{ number_format($totals['questions']) }} quiz questions</small>
    </article>
</a>
    </section>

    <section class="admin-grid">
        <article class="admin-panel">
            <div class="panel-header">
                <h2>Recent Players</h2>
            </div>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Joined</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if ($recentPlayers->isEmpty())
                            <tr><td colspan="3" class="table-empty">No players yet.</td></tr>
                        @else
                            @foreach ($recentPlayers as $player)
                                <tr>
                                    <td data-label="Username">{{ $player->username }}</td>
                                    <td data-label="Email">{{ $player->email }}</td>
                                    <td data-label="Joined">{{ $player->created_at?->format('M j, Y') }}</td>
                                </tr>
                            @endforeach
                        @endif
                    </tbody>
                </table>
            </div>
        </article>

        <article class="admin-panel">
            <div class="panel-header">
                <h2>Recent Scores</h2>
            </div>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Player</th>
                            <th>Category</th>
                            <th>Score</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if ($recentScores->isEmpty())
                            <tr><td colspan="3" class="table-empty">No scores yet.</td></tr>
                        @else
                            @foreach ($recentScores as $score)
                                <tr>
                                    <td data-label="Player">{{ $score->username }}</td>
                                    <td data-label="Category">{{ $score->category }}</td>
                                    <td data-label="Score">{{ (int) $score->score }}</td>
                                </tr>
                            @endforeach
                        @endif
                    </tbody>
                </table>
            </div>
        </article>
    </section>

    <section class="admin-grid">
        <article class="admin-panel">
            <div class="panel-header">
                <h2>Recent Admin Changes</h2>
            </div>
            <div class="feed-list">
                @if ($recentLogs->isEmpty())
                    <div class="empty-state">No recent admin activity.</div>
                @else
                    @foreach ($recentLogs as $log)
                        <div class="feed-item">
                            <strong>{{ $log->action }}</strong>
                            <span>{{ $log->admin?->username ?? 'Unknown' }}</span>
                            <small>{{ $log->resource }}</small>
                            <time>{{ $log->created_at?->format('M j, Y H:i') }}</time>
                        </div>
                    @endforeach
                @endif
            </div>
        </article>

        <article class="admin-panel">
            <div class="panel-header">
                <h2>Game Version</h2>
            </div>
            <div class="info-stack">
                @if ($currentVersion)
                    <div class="kpi-row">
                        <span>Current Version</span>
                        <strong>{{ $currentVersion->version_number }}</strong>
                    </div>
                    <div class="kpi-row">
                        <span>Platform</span>
                        <strong>{{ $currentVersion->platform }}</strong>
                    </div>
                    <div class="kpi-row">
                        <span>Status</span>
                        <strong>{{ $currentVersion->status }}</strong>
                    </div>
                    <p>{{ $currentVersion->release_notes ?: 'No release notes recorded yet.' }}</p>
                @else
                    <div class="empty-state">No active game version is configured.</div>
                @endif
            </div>
        </article>
    </section>

    <section class="admin-panel">
        <div class="panel-header">
            <h2>Recent Announcements</h2>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Status</th>
                        <th>Published Date</th>
                    </tr>
                </thead>
                <tbody>
                    @if ($recentAnnouncements->isEmpty())
                        <tr><td colspan="3" class="table-empty">No announcements yet.</td></tr>
                    @else
                        @foreach ($recentAnnouncements as $announcement)
                            <tr>
                                <td data-label="Title">{{ $announcement->title }}</td>
                                <td data-label="Status"><span class="status-badge {{ $announcement->status }}">{{ $announcement->status }}</span></td>
                                <td data-label="Published">{{ $announcement->publish_date?->format('M j, Y') ?? 'Not scheduled' }}</td>
                            </tr>
                        @endforeach
                    @endif
                </tbody>
            </table>
        </div>
    </section>
@endsection

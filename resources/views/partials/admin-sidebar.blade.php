<aside class="admin-sidebar" id="admin-sidebar" aria-label="Admin navigation">
    <div class="admin-brand">
        <img src="{{ asset('images/logo.png') }}" alt="MANGNO" class="admin-brand-logo">
        <button type="button" class="admin-sidebar-close" aria-label="Close admin menu">✕</button>
    </div>
    <nav class="admin-nav">
        <span class="admin-nav-group">Main</span>
        <a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">Dashboard</a>
        <a href="{{ route('admin.users.index') }}" class="{{ request()->routeIs('admin.users.*') ? 'active' : '' }}">Players / Users</a>
        <a href="{{ route('admin.progress.index') }}" class="{{ request()->routeIs('admin.progress.*') ? 'active' : '' }}">Progress</a>

        <span class="admin-nav-group">Game</span>
        <a href="{{ route('admin.leaderboard') }}" class="{{ request()->routeIs('admin.leaderboard') ? 'active' : '' }}">Leaderboard</a>
        <a href="{{ route('admin.announcements.index') }}" class="{{ request()->routeIs('admin.announcements.*') ? 'active' : '' }}">Announcements</a>
        <a href="{{ route('admin.versions.index') }}" class="{{ request()->routeIs('admin.versions.*') ? 'active' : '' }}">Game Versions</a>

        <span class="admin-nav-group">System</span>
        <a href="{{ route('admin.logs.index') }}" class="{{ request()->routeIs('admin.logs.*') ? 'active' : '' }}">Activity Logs</a>
        <a href="{{ route('admin.settings.edit') }}" class="{{ request()->routeIs('admin.settings.*') ? 'active' : '' }}">Settings</a>
    </nav>

    <div class="admin-sidebar-footer">
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit">Logout {{ auth()->user()->username }}</button>
        </form>
    </div>
</aside>

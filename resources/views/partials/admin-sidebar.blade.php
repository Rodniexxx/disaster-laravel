@php
    $navItems = [
        'Main' => [
            ['route' => 'admin.dashboard', 'active' => 'admin.dashboard', 'label' => 'Dashboard', 'icon' => 'grid'],
            ['route' => 'admin.users.index', 'active' => 'admin.users.*', 'label' => 'Players / Users', 'icon' => 'users'],
            ['route' => 'admin.progress.index', 'active' => 'admin.progress.*', 'label' => 'Progress', 'icon' => 'chart'],
        ],
        'Game' => [
            ['route' => 'admin.leaderboard', 'active' => 'admin.leaderboard', 'label' => 'Leaderboard', 'icon' => 'trophy'],
            ['route' => 'admin.questions.index', 'active' => 'admin.questions.*', 'label' => 'Quiz Questions', 'icon' => 'quiz'],
            ['route' => 'admin.announcements.index', 'active' => 'admin.announcements.*', 'label' => 'Announcements', 'icon' => 'megaphone'],
            ['route' => 'admin.versions.index', 'active' => 'admin.versions.*', 'label' => 'Game Versions', 'icon' => 'box'],
        ],
        'System' => [
            ['route' => 'admin.logs.index', 'active' => 'admin.logs.*', 'label' => 'Activity Logs', 'icon' => 'list'],
            ['route' => 'admin.settings.edit', 'active' => 'admin.settings.*', 'label' => 'Settings', 'icon' => 'sliders'],
        ],
    ];
@endphp

<aside class="admin-sidebar" id="admin-sidebar" aria-label="Admin navigation">
    <div class="admin-brand">
        <span class="admin-brand-mark">
            <img src="{{ asset('images/logo.png') }}" alt="MANGNO" class="admin-brand-logo">
        </span>
        <button type="button" class="admin-sidebar-close" aria-label="Close admin menu">&#10005;</button>
    </div>

    <p class="admin-console-label">Control Center</p>

    <nav class="admin-nav">
        @foreach ($navItems as $group => $items)
            <span class="admin-nav-group">{{ $group }}</span>
            @foreach ($items as $item)
                <a href="{{ route($item['route']) }}"
                   class="{{ request()->routeIs($item['active']) ? 'active' : '' }}">
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        @switch($item['icon'])
                            @case('grid')
                                <rect x="3.5" y="3.5" width="7" height="7" rx="1.5"/>
                                <rect x="13.5" y="3.5" width="7" height="7" rx="1.5"/>
                                <rect x="3.5" y="13.5" width="7" height="7" rx="1.5"/>
                                <rect x="13.5" y="13.5" width="7" height="7" rx="1.5"/>
                                @break
                            @case('users')
                                <circle cx="9" cy="8" r="3.2"/>
                                <path d="M3.5 20c.7-3.2 2.9-5 5.5-5s4.8 1.8 5.5 5"/>
                                <path d="M16 5a3 3 0 0 1 0 6"/>
                                <path d="M17 15.4c1.9.5 3 2.1 3.5 4.6"/>
                                @break
                            @case('chart')
                                <path d="M4 20V11"/>
                                <path d="M10 20V5"/>
                                <path d="M16 20v-6"/>
                                <path d="M2.5 20h19"/>
                                @break
                            @case('trophy')
                                <path d="M8 21h8"/>
                                <path d="M12 17v4"/>
                                <path d="M7 4h10v4a5 5 0 0 1-10 0V4Z"/>
                                <path d="M7 5H4v2a3 3 0 0 0 3 3"/>
                                <path d="M17 5h3v2a3 3 0 0 1-3 3"/>
                                @break
                            @case('quiz')
                                <circle cx="12" cy="12" r="8.5"/>
                                <path d="M9.6 9.2a2.5 2.5 0 1 1 3.4 2.3c-.7.3-1 .9-1 1.5v.4"/>
                                <path d="M12 16.8h.01"/>
                                @break
                            @case('megaphone')
                                <path d="M4 10v4a2 2 0 0 0 2 2h1l8 4V4L7 8H6a2 2 0 0 0-2 2Z"/>
                                <path d="M18 9a4 4 0 0 1 0 6"/>
                                @break
                            @case('box')
                                <path d="M12 3 4 7v10l8 4 8-4V7l-8-4Z"/>
                                <path d="m4 7 8 4 8-4"/>
                                <path d="M12 11v10"/>
                                @break
                            @case('list')
                                <path d="M4 6h16"/>
                                <path d="M4 12h16"/>
                                <path d="M4 18h10"/>
                                @break
                            @case('sliders')
                                <path d="M4 7h9"/>
                                <path d="M17 7h3"/>
                                <path d="M4 17h5"/>
                                <path d="M13 17h7"/>
                                <circle cx="15" cy="7" r="2"/>
                                <circle cx="11" cy="17" r="2"/>
                                @break
                        @endswitch
                    </svg>
                    <span>{{ $item['label'] }}</span>
                </a>
            @endforeach
        @endforeach
    </nav>

    <div class="admin-sidebar-footer">
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit">Logout</button>
        </form>
    </div>
</aside>

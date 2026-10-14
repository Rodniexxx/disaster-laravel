<header class="site-header">
    <nav class="navbar container" aria-label="Main navigation">
        <a href="{{ route('home') }}" class="brand" aria-label="MANGNO home">
            <span class="brand-mark">⚡</span>
            <span class="brand-text">
                <span class="brand-title">MANGNO</span>
                <span class="brand-subtitle">Ready Before It Happens.</span>
            </span>
        </a>
        <button class="nav-toggle" aria-label="Toggle navigation">☰</button>
        <div class="nav-links">
            <a href="{{ route('home') }}" class="{{ request()->routeIs('home') ? 'active' : '' }}">Home</a>
            <a href="{{ route('home') }}#disaster-guides">Disasters</a>
            <a href="{{ route('home') }}#safety-guide">Safety Guide</a>
            <a href="{{ route('leaderboard.index') }}" class="{{ request()->routeIs('leaderboard.*') ? 'active' : '' }}">Leaderboard</a>
            @auth
                <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'active' : '' }}">Play Game</a>
                <div class="nav-user">
                    <span class="nav-user-name">Hi, {{ auth()->user()->username }}</span>
                    <form method="POST" action="{{ route('logout') }}" class="inline-form">
                        @csrf
                        <button type="submit" class="nav-logout">Logout</button>
                    </form>
                </div>
            @else
                <a href="{{ route('login') }}" class="{{ request()->routeIs('login') ? 'active' : '' }}">Login</a>
                <a href="{{ route('register') }}" class="nav-register {{ request()->routeIs('register') ? 'active' : '' }}">Register</a>
            @endauth
        </div>
    </nav>
</header>

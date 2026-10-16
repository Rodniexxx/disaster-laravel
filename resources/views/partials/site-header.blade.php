<header class="site-header">
    <nav class="navbar container" aria-label="Main navigation">
        <a href="{{ route('home') }}" class="brand" aria-label="MANGNO — Home">
            <img src="{{ asset('images/logo.png') }}" alt="MANGNO" class="brand-logo">
        </a>
        <button class="nav-toggle" type="button" aria-label="Toggle navigation menu"
                aria-controls="primary-navigation" aria-expanded="false">
            <span class="nav-toggle-icon" aria-hidden="true">☰</span>
        </button>
        <div class="nav-links" id="primary-navigation">
            <a href="{{ route('home') }}" class="{{ request()->routeIs('home') ? 'active' : '' }}">Home</a>
            <a href="{{ route('home') }}#disaster-guides">Disasters</a>
            <a href="{{ route('home') }}#ans">Announcements</a>
            <a href="{{ route('home') }}#about">About</a>
            <a href="{{ route('download') }}" class="{{ request()->routeIs('download') ? 'active' : '' }}">Download</a>
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

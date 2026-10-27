<header class="site-header">
    <nav class="navbar container" aria-label="Main navigation">
        <a href="{{ route('home') }}" class="brand" aria-label="MANGNO — Home">
            <img src="{{ asset('images/logo.png') }}" alt="MANGNO" class="brand-logo">
        </a>

        <button class="nav-toggle" type="button" aria-label="Toggle navigation menu"
                aria-controls="primary-navigation" aria-expanded="false">
            <span class="nav-toggle-icon" aria-hidden="true">
                <span class="nav-toggle-bar"></span>
                <span class="nav-toggle-bar"></span>
            </span>
        </button>

        <div class="nav-links" id="primary-navigation">
            <div class="nav-primary">
                <a href="{{ route('home') }}" class="{{ request()->routeIs('home') ? 'active' : '' }}" @if(request()->routeIs('home')) aria-current="page" @endif>Home</a>
               
                   
                    
                        <a href="{{ route('home') }}#disaster-guides">Scenarios</a>
                        <a href="{{ route('home') }}#ans">Announcements</a>
                        <a href="{{ route('home') }}#about">About</a>
                 
                <a href="{{ route('leaderboard.index') }}" class="{{ request()->routeIs('leaderboard.*') ? 'active' : '' }}" @if(request()->routeIs('leaderboard.*')) aria-current="page" @endif>Leaderboard</a>
                <a href="{{ route('download') }}" class="{{ request()->routeIs('download') ? 'active' : '' }}" @if(request()->routeIs('download')) aria-current="page" @endif>Download</a>
            </div>
            <div class="nav-account">
            @auth
                <a href="{{ route('dashboard') }}" class="nav-register {{ request()->routeIs('dashboard') ? 'active' : '' }}" @if(request()->routeIs('dashboard')) aria-current="page" @endif>Dashboard</a>
                <div class="nav-user">
                    <a href="{{ route('profile.edit') }}" aria-label="Edit profile picture"><x-player-avatar :user="auth()->user()" :size="32" /></a>
                    <span class="nav-user-name">Hi, {{ auth()->user()->username }}</span>
                    <form method="POST" action="{{ route('logout') }}" class="inline-form">
                        @csrf
                        <button type="submit" class="nav-logout">Logout</button>
                    </form>
                </div>
            @else
                <a href="{{ route('login') }}" class="{{ request()->routeIs('login') ? 'active' : '' }}">Login</a>
                <a href="{{ route('register') }}" class="nav-register {{ request()->routeIs('register') ? 'active' : '' }}">Create account</a>
            @endauth
            </div>
        </div>
    </nav>
</header>

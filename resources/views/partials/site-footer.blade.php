<footer class="site-footer">
    <div class="container footer-content">
        <div class="footer-brand">
            <a href="{{ route('home') }}" class="footer-brand-link" aria-label="MANGNO — Home">
                <img src="{{ asset('images/logo.png') }}" alt="MANGNO" class="footer-logo">
            </a>
            <p class="footer-note">An educational game for disaster preparedness.</p>
        </div>

        <nav class="footer-nav" aria-label="Footer navigation">
            <div>
                <h2 class="footer-heading">Explore</h2>
                <a href="{{ route('home') }}">Home</a>
                <a href="{{ route('home') }}#disaster-guides">Disaster scenarios</a>
                <a href="{{ route('home') }}#safety-guide">Safety guide</a>
                <a href="{{ route('home') }}#emergency-info">Emergency information</a>
            </div>
            <div>
                <h2 class="footer-heading">Game</h2>
                <a href="{{ route('download') }}">Download</a>
                <a href="{{ route('leaderboard.index') }}">Leaderboard</a>
                @auth
                    <a href="{{ route('dashboard') }}">Dashboard</a>
                @else
                    <a href="{{ route('login') }}">Login</a>
                    <a href="{{ route('register') }}">Register</a>
                @endauth
            </div>
        </nav>
    </div>

    <div class="container footer-bottom">
        <p>&copy; {{ date('Y') }} MANGNO &middot; Educational Simulation Game</p>
        <p>Ready Before It Happens.</p>
    </div>
</footer>

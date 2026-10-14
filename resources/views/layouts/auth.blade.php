<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('partials.head')
</head>
<body class="auth-body">
    @include('partials.site-header')

    <main class="auth-page">
        <div class="auth-shell reveal">
            <div class="auth-visual">
                <div class="auth-visual-inner">
                    <p class="auth-kicker">@yield('visual-kicker')</p>
                    <h1>@yield('visual-title')</h1>
                    <p>@yield('visual-text')</p>
                    <ul class="auth-points">
                        @yield('visual-points')
                    </ul>
                </div>
            </div>

            <div class="auth-card glass-panel">
                <div class="auth-card-header">
                    <p class="eyebrow">@yield('card-eyebrow')</p>
                    <h2>@yield('card-title')</h2>
                </div>

                @include('partials.flash')

                @yield('content')
            </div>
        </div>
    </main>

    @include('partials.site-footer')
</body>
</html>

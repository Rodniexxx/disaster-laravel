<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('partials.head')
</head>
<body class="auth-body">
    <a href="#main-content" class="skip-link">Skip to main content</a>

    @include('partials.site-header')

    <main id="main-content" class="auth-page">
        <div class="auth-shell reveal">
            <div class="auth-visual">
                <div class="auth-visual-inner">
                    <p class="auth-kicker">@yield('visual-kicker')</p>
                    <h1>@yield('visual-title')</h1>
                    <p>@yield('visual-text')</p>
                    @hasSection('visual-points')
                        <ul class="auth-points">
                            @yield('visual-points')
                        </ul>
                    @endif
                </div>
            </div>

            <div class="auth-card">
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

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('partials.head')
</head>
<body class="admin-body">
    <a href="#main-content" class="skip-link">Skip to main content</a>
    <div class="admin-layout">
        @include('partials.admin-sidebar')
        <div class="admin-backdrop" data-admin-backdrop hidden></div>

        <main id="main-content" class="admin-main">
            <header class="admin-topbar">
                <div class="admin-topbar-main">
                    <button type="button" class="admin-menu-toggle" aria-label="Open admin menu"
                            aria-controls="admin-sidebar" aria-expanded="false">&#9776;</button>
                    <div>
                        @hasSection('back')
                            @yield('back')
                        @endif
                        <p class="eyebrow">@yield('eyebrow', 'Admin')</p>
                        <h1>@yield('heading', trim($__env->yieldContent('title')) ?: 'Admin')</h1>
                        @hasSection('subheading')
                            <p class="admin-topbar-sub">@yield('subheading')</p>
                        @endif
                    </div>
                </div>
                <div class="topbar-user">
                    <span>Signed in as <strong class="topbar-user-name">{{ auth()->user()->username }}</strong></span>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="btn btn-secondary small">Logout</button>
                    </form>
                </div>
            </header>

            @include('partials.flash')

            @yield('content')
        </main>
    </div>
</body>
</html>

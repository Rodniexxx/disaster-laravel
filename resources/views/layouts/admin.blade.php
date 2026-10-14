<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('partials.head')
</head>
<body class="admin-body">
    <div class="admin-layout">
        @include('partials.admin-sidebar')

        <main class="admin-main">
            <header class="admin-topbar">
                <div>
                    <p class="eyebrow">@yield('eyebrow', 'Admin')</p>
                    <h1>@yield('heading', trim($__env->yieldContent('title')) ?: 'Admin')</h1>
                    @hasSection('subheading')
                        <p class="admin-topbar-sub">@yield('subheading')</p>
                    @endif
                </div>
                <div class="topbar-user">
                    <span>Logged in as {{ auth()->user()->username }}</span>
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

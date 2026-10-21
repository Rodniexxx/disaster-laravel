<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('partials.head')
</head>
<body class="@yield('body-class')">
    <a href="#main-content" class="skip-link">Skip to main content</a>

    @include('partials.site-header')

    <main id="main-content" class="@yield('main-class')">
        @include('partials.flash')
        @yield('content')
    </main>

    @include('partials.site-footer')
</body>
</html>

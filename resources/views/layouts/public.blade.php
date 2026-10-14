<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('partials.head')
</head>
<body class="@yield('body-class')">
    @include('partials.site-header')

    <main class="@yield('main-class')">
        @include('partials.flash')
        @yield('content')
    </main>

    @include('partials.site-footer')
</body>
</html>

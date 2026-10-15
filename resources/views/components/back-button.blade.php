@props([
    'fallback' => null,
    'label' => 'Back',
])

@php
    use Illuminate\Support\Str;

    // Resolve the safe destination used when there is no usable in-app history
    // (for example when the page is opened directly from a bookmark or link).
    if ($fallback) {
        $fallbackUrl = Str::startsWith($fallback, ['http://', 'https://', '/'])
            ? $fallback
            : route($fallback);
    } elseif (request()->routeIs('admin.*')) {
        $fallbackUrl = route('admin.dashboard');
    } elseif (auth()->check()) {
        $fallbackUrl = route('dashboard');
    } else {
        $fallbackUrl = route('home');
    }
@endphp

<a href="{{ $fallbackUrl }}"
   {{ $attributes->merge(['class' => 'back-button']) }}
   data-back-button
   rel="back"
   aria-label="Go back to the previous page">
    <span class="back-button-icon" aria-hidden="true">&larr;</span>
    <span class="back-button-label">{{ $label }}</span>
</a>

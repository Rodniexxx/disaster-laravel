@props(['user' => null, 'url' => null, 'size' => 36])
@php
    $fallback = app(\App\Services\AvatarService::class)->defaultUrl();
    $picture = $url ?? $user?->profile_picture_url ?? $fallback;
    $pixels = max(24, min(128, (int) $size));
@endphp
<img {{ $attributes->class(['player-avatar']) }} src="{{ $picture }}" alt="" width="{{ $pixels }}" height="{{ $pixels }}" style="--avatar-size: {{ $pixels }}px" loading="lazy" data-avatar data-fallback="{{ $fallback }}">

@extends('layouts.public')

@section('title', 'Download MANGNO')
@section('body-class', 'home-page')
@section('main-class', '')

@php
    $beforeNotes = array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', (string) ($settings['download_before_notes'] ?? ''))));
@endphp

@section('content')
    <section class="download-hero">
        <div class="container">
            @if (trim((string) ($settings['download_eyebrow'] ?? '')) !== '')
                <p class="eyebrow reveal-item">{{ $settings['download_eyebrow'] }}</p>
            @endif
            <h1 class="download-hero-title reveal-item">{{ $settings['download_heading'] }}</h1>
            @if (trim((string) ($settings['download_intro'] ?? '')) !== '')
                <p class="download-hero-text reveal-item">{{ $settings['download_intro'] }}</p>
            @endif
            <div class="download-hero-actions reveal-item">
                <a href="#downloads" class="btn btn-primary">Download Now</a>
            </div>
        </div>
    </section>

    <section class="section container" id="downloads">
        <div class="section-heading reveal-item">
            @if (trim((string) ($settings['download_section_eyebrow'] ?? '')) !== '')
                <p class="eyebrow">{{ $settings['download_section_eyebrow'] }}</p>
            @endif
            <h2>{{ $settings['download_section_heading'] }}</h2>
        </div>

        <div class="download-grid">
            @foreach ($platforms as $platform)
                <article class="download-card reveal-item">
                    <div class="download-card-head">
                        <span class="download-icon" aria-hidden="true">
                            @if ($platform['key'] === 'windows')
                                <svg viewBox="0 0 24 24"><path d="M3 4.6 10.3 3.6v7.2H3V4.6Zm0 7.2h7.3v7.2L3 17.9v-6.1Zm8.3-8.3L21 2.3v8.5h-9.7V3.5Zm0 8.5H21v8.5l-9.7-1.3v-7.2Z"/></svg>
                            @else
                                <svg viewBox="0 0 24 24"><path d="M7.6 3.3l.9 1.6a5.8 5.8 0 0 1 7 0l.9-1.6a.3.3 0 0 1 .5.3l-.9 1.6A5.4 5.4 0 0 1 19 9.5H5a5.4 5.4 0 0 1 2.6-4.2l-.9-1.6a.3.3 0 0 1 .5-.3ZM9 7.3a.7.7 0 1 0 0-1.4.7.7 0 0 0 0 1.4Zm6 0a.7.7 0 1 0 0-1.4.7.7 0 0 0 0 1.4ZM5 10.5h14v6.7A2.3 2.3 0 0 1 16.7 19H7.3A2.3 2.3 0 0 1 5 17.2v-6.7Zm-1.5.2A1.3 1.3 0 0 1 4.8 12v3.5a1.3 1.3 0 0 1-2.6 0V12a1.3 1.3 0 0 1 1.3-1.3Zm17 0A1.3 1.3 0 0 1 21.8 12v3.5a1.3 1.3 0 1 1-2.6 0V12a1.3 1.3 0 0 1 1.3-1.3ZM8 19.4h2v2a1 1 0 1 1-2 0v-2Zm6 0h2v2a1 1 0 1 1-2 0v-2Z"/></svg>
                            @endif
                        </span>
                        <div class="download-card-title">
                            <h3>{{ $platform['title'] }}</h3>
                            <span class="download-platform">{{ $platform['platform'] }}</span>
                        </div>
                        @if ($platform['available'])
                            <span class="download-status">{{ $platform['status'] }}</span>
                        @endif
                    </div>

                    @if (trim((string) $platform['description']) !== '')
                        <p class="download-card-text">{{ $platform['description'] }}</p>
                    @endif

                    @if (trim((string) ($platform['release_notes'] ?? '')) !== '')
                        <p class="download-notes">{{ $platform['release_notes'] }}</p>
                    @endif

                    <ul class="download-meta">
                        <li><span>Version</span><strong>{{ $platform['version'] ?: '—' }}</strong></li>
                        <li><span>File name</span><strong>{{ $platform['file_name'] ?: '—' }}</strong></li>
                        <li><span>File type</span><strong>{{ $platform['file_type'] ?: '—' }}</strong></li>
                        <li><span>File size</span><strong>{{ $platform['size'] ?: '—' }}</strong></li>
                        <li><span>System requirements</span><strong>{{ $platform['requirements'] ?: '—' }}</strong></li>
                        <li><span>Released</span><strong>{{ $platform['release_date']?->format('M j, Y') ?: '—' }}</strong></li>
                    </ul>

                    <div class="download-card-action">
                        @if ($platform['available'])
                            <a href="{{ $platform['url'] }}" class="btn btn-outline full-width" download>{{ $platform['button'] }}</a>
                        @else
                            <span class="btn btn-outline full-width is-disabled" aria-disabled="true">{{ $platform['button'] }}</span>
                            <p class="download-note">Coming soon &mdash; this version is not currently available for download.</p>
                        @endif
                    </div>
                </article>
            @endforeach
        </div>
    </section>

    @if (trim((string) ($settings['download_before_heading'] ?? '')) !== '' || $beforeNotes !== [])
        <section class="section container" id="before-you-download">
            <div class="section-heading reveal-item">
                <h2>{{ $settings['download_before_heading'] }}</h2>
            </div>

            <div class="about-panel reveal-item">
                <ul class="download-checklist">
                    @foreach ($beforeNotes as $note)
                        <li>{{ $note }}</li>
                    @endforeach
                </ul>
            </div>
        </section>
    @endif

    @if (trim((string) ($settings['download_about'] ?? '')) !== '')
        <section class="section container about-section" id="about">
            <div class="section-heading reveal-item">
                <p class="eyebrow">About</p>
                <h2>MANGNO</h2>
            </div>

            <div class="about-panel reveal-item">
                <p class="about-lead">{{ $settings['download_about'] }}</p>
            </div>
        </section>
    @endif

    @if (trim((string) ($settings['download_extra'] ?? '')) !== '')
        <section class="section container">
            <div class="section-heading reveal-item">
                @if (trim((string) ($settings['download_extra_heading'] ?? '')) !== '')
                    <h2>{{ $settings['download_extra_heading'] }}</h2>
                @endif
            </div>

            <div class="about-panel reveal-item">
                <p class="about-lead">{{ $settings['download_extra'] }}</p>
            </div>
        </section>
    @endif
@endsection

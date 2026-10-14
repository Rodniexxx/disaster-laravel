@extends('layouts.public')

@php
    $siteName = $settings['site_name'] ?? 'MANGNO';
    $tagline = $settings['site_tagline'] ?? 'Ready Before It Happens.';
    $emojis = ['earthquake' => '🌎', 'fire' => '🔥', 'typhoon' => '🌧️', 'volcano' => '⛰️'];
    $featured = $scenarios->first();
    $startUrl = auth()->check() ? route('dashboard') : route('login');
@endphp

@section('title', $siteName.' · '.$tagline)
@section('body-class', 'home-page')
@section('main-class', '')

@section('content')
    <section class="hero" aria-label="Disaster preparedness hero section">
        <div class="hero-bg" aria-hidden="true">
            <div class="hero-layer hero-base"></div>
            <div class="hero-layer hero-reveal"></div>
            <div class="hero-vignette"></div>
        </div>

        <div class="container hero-inner">
            <div class="hero-copy reveal-item">
                <p class="eyebrow">School Safety Program</p>
                <h1 class="hero-title" aria-label="Disaster preparedness simulation">
                    <span class="title-word" style="--index: 0;">DISASTER</span>
                    <span class="title-word" style="--index: 1;">PREPAREDNESS</span>
                    <span class="title-word" style="--index: 2;">SIMULATION</span>
                </h1>
                <p class="hero-subtitle">Learn how to respond before, during, and after disasters through interactive survival scenarios.</p>

                <div class="hero-actions">
                    <a href="{{ $startUrl }}" class="btn btn-primary">START TRAINING</a>
                    <a href="#disaster-guides" class="btn btn-secondary">VIEW SCENARIOS</a>
                </div>

                <div class="quick-icons" aria-label="Quick disaster preparation links">
                    <a href="#disaster-guides" class="icon-button" aria-label="Training exercises">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2.75a7.25 7.25 0 0 1 7.25 7.25c0 4.83-3.48 9.09-8 11.36-4.52-2.27-8-6.53-8-11.36A7.25 7.25 0 0 1 12 2.75Zm0 3.25a3.5 3.5 0 1 0 0 7 3.5 3.5 0 0 0 0-7Zm-5.7 9.03a7.95 7.95 0 0 0 11.4 0l-1.76-1.46A5.46 5.46 0 0 1 12 15.5a5.46 5.46 0 0 1-3.94-1.68L7.3 15.03Z"/></svg>
                        <span>Training</span>
                    </a>
                    <a href="#safety-guide" class="icon-button" aria-label="Safety guide">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2.5 5 5v6.2c0 4.22 2.64 8.15 7 10.3 4.36-2.15 7-6.08 7-10.3V5l-7-2.5Zm0 3.22 3.5 1.26v3.97c0 2.76-1.7 5.32-3.5 6.63-1.8-1.31-3.5-3.87-3.5-6.63V7l3.5-1.28Zm-1.2 2.78h2.4v2.9h2.9v2.4h-2.9v2.9h-2.4v-2.9H8.1v-2.4h2.7v-2.9Z"/></svg>
                        <span>Safety Guide</span>
                    </a>
                    <a href="#emergency-info" class="icon-button" aria-label="Emergency information">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M11.25 3.5a8.75 8.75 0 1 1 0 17.5 8.75 8.75 0 0 1 0-17.5Zm0 4.25a1.2 1.2 0 0 0-1.2 1.2v3.65a1.2 1.2 0 0 0 2.4 0V9.0a1.2 1.2 0 0 0-1.2-1.25Zm0 8.4a1.32 1.32 0 1 0 0-2.64 1.32 1.32 0 0 0 0 2.64Z"/></svg>
                        <span>Emergency Info</span>
                    </a>
                </div>
            </div>

            <div class="hero-side">
                <div class="page-indicator reveal-item">
                    <span class="page-number">01 / 04</span>
                    <span class="page-label">Disaster Training</span>
                </div>

                <div class="featured-card glass-card reveal-item">
                    <p class="card-kicker">Featured Scenario</p>
                    <h2>{{ strtoupper($featured->title ?? 'EARTHQUAKE') }}</h2>
                    <p class="scenario-tag">{{ $featured->difficulty ?? 'Drop • Cover • Hold' }}</p>
                    <p>{{ $featured->description ?? 'Learn the correct actions to take during strong earthquake shaking.' }}</p>
                    <a href="{{ $startUrl }}" class="inline-link">START TRAINING</a>
                </div>

                <div class="module-card glass-card reveal-item">
                    <p class="card-kicker">Training Modules</p>
                    <div class="spec-list" aria-label="Training module list">
                        @foreach ($scenarios as $scenario)
                            <div class="spec-row">
                                <span>{{ $scenario->title }}</span>
                                <strong>{{ $scenario->difficulty ?: 'Stay Ready' }}</strong>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="section container scenario-section" id="disaster-guides">
        <div class="section-heading reveal-item">
            <p class="eyebrow">Emergency Topics</p>
            <h2>Simulation Scenarios</h2>
        </div>

        @if ($scenarios->isEmpty())
            <div class="empty-state">No scenarios are available yet. Please check back soon.</div>
        @else
            <div class="cards">
                @foreach ($scenarios as $scenario)
                    <article class="card reveal-item">
                        <div class="card-icon" aria-hidden="true">{{ $emojis[strtolower($scenario->slug)] ?? '🌎' }}</div>
                        <span class="card-tag">{{ $scenario->title }}</span>
                        <h3>{{ $scenario->title }}</h3>
                        <p>{{ \Illuminate\Support\Str::limit($scenario->description, 120) ?: 'Learn safe actions and response steps.' }}</p>
                    </article>
                @endforeach
            </div>
        @endif
    </section>

    @include('partials.safety-guide')

    @if ($announcements->isNotEmpty())
        <section class="section container scenario-section">
            <div class="section-heading reveal-item">
                <p class="eyebrow">Latest Updates</p>
                <h2>Announcements</h2>
            </div>

            <div class="cards">
                @foreach ($announcements as $announcement)
                    <article class="card reveal-item">
                        <div class="card-icon" aria-hidden="true">📣</div>
                        <span class="card-tag">Announcement</span>
                        <h3>{{ $announcement->title }}</h3>
                        <p>{{ $announcement->message }}</p>
                        @if ($announcement->publish_date)
                            <p class="muted">{{ $announcement->publish_date->format('M j, Y') }}</p>
                        @endif
                    </article>
                @endforeach
            </div>
        </section>
    @endif
@endsection

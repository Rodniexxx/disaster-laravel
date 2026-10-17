@extends('layouts.public')

@php
    $siteName = $settings['site_name'] ?? 'MANGNO';
    $tagline = $settings['site_tagline'] ?? 'Ready Before It Happens.';
    $emojis = ['earthquake' => '🌎', 'fire' => '🔥', 'typhoon' => '🌧️', 'volcano' => '⛰️'];
    $featured = $scenarios->first();
    $startUrl = auth()->check() ? route('dashboard') : route('login');
    $carousel = $scenarios->values()->map(fn ($scenario) => [
        'title' => strtoupper($scenario->title),
        'description' => $scenario->description ?: 'Learn safe actions and response steps.',
    ])->all();
    $slideTotal = max($scenarios->count(), 1);
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
                <p class="eyebrow">Disaster Preparedness</p>
                <h1 class="hero-title" aria-label="Disaster preparedness simulation">
                    <span class="title-word" style="--index: 0;">EDUCATIONAL</span>
                    <span class="title-word" style="--index: 1;">SIMULATION</span>
                    <span class="title-word" style="--index: 2;">GAME</span>
                </h1>
                <p class="hero-subtitle">Learn how to respond before, during, and after disasters through interactive survival scenarios.</p>

                <div class="hero-actions">
                    <a href="{{ $startUrl }}" class="btn btn-primary">START PLAYING</a>
                    <a href="#disaster-guides" class="btn btn-secondary">VIEW SCENARIOS</a>
                </div>

                <div class="quick-icons" aria-label="Quick disaster preparation links">
                    <a href="#disaster-guides" class="icon-button" aria-label="Scenarios — training exercises">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2.75a7.25 7.25 0 0 1 7.25 7.25c0 4.83-3.48 9.09-8 11.36-4.52-2.27-8-6.53-8-11.36A7.25 7.25 0 0 1 12 2.75Zm0 3.25a3.5 3.5 0 1 0 0 7 3.5 3.5 0 0 0 0-7Zm-5.7 9.03a7.95 7.95 0 0 0 11.4 0l-1.76-1.46A5.46 5.46 0 0 1 12 15.5a5.46 5.46 0 0 1-3.94-1.68L7.3 15.03Z"/></svg>
                        <span>Scenarios</span>
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

            <div class="hero-side" data-scenario-carousel>
                <div class="page-indicator reveal-item">
                    <span class="page-number" data-carousel-number>01 / {{ str_pad((string) $slideTotal, 2, '0', STR_PAD_LEFT) }}</span>
                    <span class="page-label" data-carousel-label>{{ strtoupper($featured->title ?? 'Disaster Training') }}</span>
                </div>

                <div class="featured-card glass-card reveal-item" data-carousel-feature>
                    <p class="card-kicker">Featured Scenario</p>
                    <h2 data-carousel-title>{{ strtoupper($featured->title ?? 'EARTHQUAKE') }}</h2>
                    <p data-carousel-description>{{ $featured->description ?? 'Learn the correct actions to take during strong earthquake shaking.' }}</p>
                    <a href="{{ $startUrl }}" class="inline-link">SIMULATE DISASTER</a>
                </div>

                <div class="module-card glass-card reveal-item">
                    <p class="card-kicker">CATEGORIES</p>
                    <div class="spec-list" aria-label="Training module list">
                        @foreach ($scenarios as $scenario)
                            <div class="spec-row" data-carousel-row>
                                <span>{{ $scenario->title }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <script type="application/json" data-scenario-data>@json($carousel)</script>
        </div>
    </section>

    
<section class="section container scenario-section" id="disaster-guides">
    <div class="section-heading reveal-item">
        <p class="eyebrow">Emergency Topics</p>
        <h2>Simulation Scenarios</h2>
        <p class="section-description">
            Explore disaster scenarios and learn the essential actions
            to protect yourself and others during emergencies.
        </p>
    </div>

    <div class="cards scenario-grid">

        <!-- Typhoon -->
        <article class="card card-scenario reveal-item"
                 style="--card-bg: url('{{ asset('images/scenarios/typhoon.webp') }}')">
            <span class="card-tag">Natural Disaster</span>
            <h3>Typhoon</h3>
            <p>
                Learn how to prepare for strong winds, heavy rainfall,
                flooding, and possible evacuation during a typhoon.
            </p>
        </article>

        <!-- Earthquake -->
        <article class="card card-scenario reveal-item"
                 style="--card-bg: url('{{ asset('images/scenarios/earthquake.webp') }}')">
            <span class="card-tag">Natural Disaster</span>
            <h3>Earthquake</h3>
            <p>
                Practice Drop, Cover, and Hold On, identify safe areas,
                and understand what to do before and after an earthquake.
            </p>
        </article>

        <!-- Volcanic Eruption -->
        <article class="card card-scenario reveal-item"
                 style="--card-bg: url('{{ asset('images/scenarios/volcanic-eruption.webp') }}')">
            <span class="card-tag">Natural Disaster</span>
            <h3>Volcanic Eruption</h3>
            <p>
                Prepare for ashfall, follow evacuation instructions,
                and learn how to protect yourself during volcanic activity.
            </p>
        </article>

        <!-- Fire -->
        <article class="card card-scenario reveal-item"
                 style="--card-bg: url('{{ asset('images/scenarios/fire.webp') }}')">
            <span class="card-tag">Human-Caused Hazard</span>
            <h3>Fire</h3>
            <p>
                Learn fire prevention, recognize emergency warning signs,
                and practice safe evacuation during a fire.
            </p>
        </article>

    </div>
</section>


    @include('partials.safety-guide')

    <section class="section container about-section" id="about">
        <div class="section-heading reveal-item">
            <p class="eyebrow">About</p>
            <h2>MANGNO</h2>
        </div>

        <div class="about-panel reveal-item">
            <p class="about-lead">
                MANGNO is an educational game developed and designed by Joshua Brondial, Rodnie Calmada,
                John Michael Borlagdan, Lee Daves Harvy Buenconsejo, and Glendon James Mapa &mdash; 4th-year
                Bachelor of Science in Computer Science students of Dr. Ruby Lanting Casaul Educational
                Foundation, Inc., as part of their thesis study entitled
                <em>&ldquo;Mangno: Developing and Pilot Testing of an Educational Preparedness Game.&rdquo;</em>
            </p>
            <p class="about-lead">
                The project aims to promote disaster preparedness awareness and knowledge through interactive
                gameplay, simulated disaster scenarios, and educational challenges. By allowing players to
                experience different hazards, including typhoons, earthquakes, volcanic eruptions, and fires. Mangno seeks to help users understand appropriate preparedness
                measures and responses in a more engaging and accessible learning environment. Through pilot
                testing and participant feedback, the researchers also aim to evaluate the game&rsquo;s
                efficiency, acceptability, and overall user experience to identify opportunities for further
                improvement.
            </p>

            <hr class="about-divider">

            <div class="about-columns">

                <div class="about-block">
                    <h3>Developed &amp; Designed By</h3>
                    <ul class="about-devs">
                        <p>Joshua Brondial</p>
                        <p>Rodnie Calmada</p>
                        <p>John Michael Borlagdan</p>
                        <p>Lee Daves Harvy Buenconsejo</p>
                        <p>Glendon James Mapa</p>
                    </ul>
                </div>
            </div>

            
        </div>
    </section>

    @if ($announcements->isNotEmpty())
        <section class="section container scenario-section" id="ans">
            <div class="section-heading reveal-item">
                <p class="eyebrow">Latest Updates</p>
                <h2>Announcements</h2>
            </div>

            <div class="cards">
                @foreach ($announcements as $announcement)
                    <article class="card reveal-item">
                        
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

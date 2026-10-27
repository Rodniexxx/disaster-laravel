@extends('layouts.public')

@php
    $siteName = $settings['site_name'] ?? 'Mangno';
    $tagline = $settings['site_tagline'] ?? 'Educational Simulation Game';
    $directory = [
        ['title' => 'Earthquake', 'slug' => 'earthquake', 'copy' => 'Practice Drop, Cover, and Hold On.'],
        ['title' => 'Fire', 'slug' => 'fire', 'copy' => 'Recognize risks and plan a safe exit.'],
        ['title' => 'Typhoon', 'slug' => 'typhoon', 'copy' => 'Prepare for strong winds and flooding.'],
        ['title' => 'Volcanic Eruption', 'slug' => 'volcanic-eruption', 'copy' => 'Understand ashfall and evacuation.'],
    ];
@endphp

@section('title', $siteName.' · '.$tagline)
@section('body-class', 'home-page')
@section('main-class', '')

@section('content')
    @include('partials.game-showcase')

    <section class="container home-directory" id="disaster-guides" aria-labelledby="directory-title">
        <div class="section-heading">
            <h2 id="directory-title">Choose your scenario</h2>
            <p class="section-description">Four hazards. Decisions that matter. Select a scenario to read its safety guide.</p>
        </div>
        <div class="home-directory-layout">
            <div class="scenario-directory">
                @foreach ($directory as $item)
                    <a class="scenario-row hazard-{{ $item['slug'] }}" href="#guide-{{ $item['slug'] }}">
                        <img src="{{ asset('images/scenarios/'.$item['slug'].'.webp') }}" width="240" height="160" alt="" loading="lazy">
                        <div class="scenario-row-copy">
                            <h3>{{ $item['title'] }}</h3>
                            <p>{{ $item['copy'] }}</p>
                        </div>
                        <span class="scenario-row-arrow" aria-hidden="true">↗</span>
                    </a>
                @endforeach
            </div>
            <aside class="player-entry" aria-label="Player access and updates">
                <div>
                    @auth
                        <h3>Your preparedness journey</h3>
                        <p>Review your scores and see how your readiness is progressing.</p>
                        <a href="{{ route('dashboard') }}" class="btn btn-primary full-width">Open dashboard</a>
                    @else
                        <h3>Already playing?</h3>
                        <p>Sign in to review your scores and compare your results.</p>
                        <a href="{{ route('login') }}" class="btn btn-primary full-width">Sign in</a>
                    @endauth
                    <a href="{{ route('leaderboard.index') }}" class="inline-link">View leaderboard</a>
                </div>
                <div class="player-entry-updates">
                    <h3>Announcements</h3>
                    <p>Game updates and community notices.</p>
                    <a href="#ans" class="inline-link">View announcements</a>
                </div>
            </aside>
        </div>
    </section>

    @include('partials.safety-guide')

    <section class="section container about-section" id="about">
        <div class="section-heading reveal-item">
            <h2>Made for preparedness</h2>
            <figure class="about-game-image">
                <img src="{{ asset('images/tindahan.jpg') }}" width="1600" height="900" alt="A neighborhood store inside the MANGNO game" loading="lazy">
                <figcaption>A familiar place to learn a vital response.</figcaption>
            </figure>
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


            <div class="about-columns">

                <div class="about-block">
                    <h3>Developed &amp; Designed By</h3>
                    <ul class="about-devs">
                        <li>Joshua Brondial</li>
                        <li>Rodnie Calmada</li>
                        <li>John Michael Borlagdan</li>
                        <li>Lee Daves Harvy Buenconsejo</li>
                        <li>Glendon James Mapa</li>
                    </ul>
                </div>
            </div>

            
        </div>
    </section>


    <section class="section container announcements-section" id="ans">
        <div class="section-heading">
            <h2>Announcements</h2>
        </div>
        <div class="announcement-list">
            @forelse ($announcements as $announcement)
                <article class="announcement">
                    <div class="announcement-meta">
                        <span class="card-tag">Update</span>
                        @if ($announcement->publish_date)
                            <time datetime="{{ $announcement->publish_date->format('Y-m-d') }}">{{ $announcement->publish_date->format('M j, Y') }}</time>
                        @endif
                    </div>
                    <div><h3>{{ $announcement->title }}</h3><p>{{ $announcement->message }}</p></div>
                </article>
            @empty
                <p class="empty-state">No announcements yet. Check back for game updates and community notices.</p>
            @endforelse
        </div>
    </section>
@endsection

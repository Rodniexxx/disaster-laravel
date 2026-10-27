@php
    $gameScenes = [
        ['file' => 'earthquake-scene.jpg', 'width' => 549, 'height' => 274, 'title' => 'Earthquake', 'category' => 'earthquake', 'caption' => 'Learn safe responses in a familiar classroom setting.', 'alt' => 'MANGNO characters at classroom computers in the earthquake scenario.'],
        ['file' => 'fire-scene.jpg', 'width' => 849, 'height' => 424, 'title' => 'Fire', 'category' => 'fire', 'caption' => 'Recognize the danger. Plan your next move.', 'alt' => 'A house on fire behind a neighborhood fence inside MANGNO.'],
        ['file' => 'fire-scene2.jpg', 'width' => 549, 'height' => 274, 'title' => 'Emergency response', 'category' => 'fire', 'caption' => 'See the community response unfold inside MANGNO.', 'alt' => 'Firefighters and a fire engine on a neighborhood street inside MANGNO.'],
        ['file' => 'typhoon-scene.jpg', 'width' => 569, 'height' => 284, 'title' => 'Typhoon', 'category' => 'typhoon', 'caption' => 'Stay informed and prepare before the storm.', 'alt' => 'A radio on a table in the MANGNO typhoon scenario.'],
        ['file' => 'volcano-scene.jpg', 'width' => 993, 'height' => 496, 'title' => 'Volcanic eruption', 'category' => 'volcanic-eruption', 'caption' => 'Understand the warning signs and prepare a safe response.', 'alt' => 'A volcano releasing ash beneath a gray sky inside MANGNO.'],
    ];
@endphp
<section class="container home-intro floating-hero" data-game-showcase aria-labelledby="home-title">
    <div class="home-intro-copy">
        <p class="hero-intro-label">Learn. Prepare. Stay safe.</p>
        <h1 id="home-title">{{ $tagline }}</h1>
        <p class="home-intro-description">Explore Mangno, a disaster preparedness game set in familiar communities. Learn your response before you need it.</p>
        <div class="home-intro-actions">
            <a href="{{ route('download') }}" class="btn btn-primary">Download the game</a>
            <a href="#disaster-guides" class="inline-link">Explore scenarios</a>
        </div>
        <p class="home-intro-note">Play the scenarios. Review your progress in your dashboard.</p>
    </div>
    <div class="hero-scenario-stack">
        <div class="showcase-scenes" role="region" aria-label="Featured MANGNO scenarios" aria-roledescription="carousel">
            @foreach ($gameScenes as $scene)
                <figure class="showcase-slide {{ $loop->first ? 'is-current' : '' }}" data-game-scene data-category="{{ $scene['category'] }}" aria-hidden="{{ $loop->first ? 'false' : 'true' }}" role="group" aria-roledescription="slide" aria-label="{{ $scene['title'] }}">
                    <div class="showcase-image"><img src="{{ asset('images/game-snippets/'.$scene['file']) }}" width="{{ $scene['width'] }}" height="{{ $scene['height'] }}" alt="{{ $scene['alt'] }}" @if ($loop->first) fetchpriority="high" @else loading="lazy" @endif></div>
                    <figcaption><span class="featured-label">Featured scenario</span><strong>{{ $scene['title'] }}</strong><span>{{ $scene['caption'] }}</span></figcaption>
                </figure>
            @endforeach
        </div>
        <nav class="hero-scenario-links" aria-label="Explore disaster guides">
            <p>Explore the scenarios</p>
            @foreach ($directory as $item)
                <a href="#guide-{{ $item['slug'] }}" data-scene-category="{{ $item['slug'] }}" class="{{ $loop->first ? 'is-featured' : '' }}"><span>{{ $item['title'] }}</span><span aria-hidden="true">↗</span></a>
            @endforeach
        </nav>
    </div>
</section>


@extends('layouts.app')

@section('title', 'Dashboard')

@php
    use App\Services\PlayerStatsService;
@endphp

@section('content')
    <section class="dashboard-header reveal">
        <div>
            <p class="eyebrow">Preparedness Center</p>
            <h1>Welcome, {{ auth()->user()->username }}</h1>
        </div>

        <div class="dashboard-header-actions">
            <span class="welcome-tag">Player profile</span>
            <a href="{{ route('leaderboard.index') }}" class="btn btn-secondary">
                Leaderboard
            </a>
        </div>
    </section>

    <section class="stats-strip reveal">
        <div class="stat-panel highlight">
            <span class="stat-label">Overall score</span>
            <strong class="stat-value">
                {{ $stats['overall'] }}<span>/100</span>
            </strong>
            <small>Across all disaster categories</small>
        </div>

        <div class="stat-panel">
            <span class="stat-label">Best score</span>
            <strong class="stat-value">
                {{ $stats['best'] }}<span>/100</span>
            </strong>
            <small>Highest scenario result</small>
        </div>

        <div class="stat-panel">
            <span class="stat-label">Leaderboard rank</span>
            <strong class="stat-value">
                {{ $stats['rank'] ? '#'.$stats['rank'] : 'N/A' }}
            </strong>
            <small>
                {{ $stats['rank'] ? 'Current standing' : 'Complete a challenge to unlock' }}
            </small>
        </div>
    </section>

    <section class="progress-grid">
        @foreach ($stats['scenarios'] as $scenario)
            @php
                $slug = $scenario->slug;
                $hasScore = $stats['hasScore'][$slug] ?? false;
                $score = (int) ($stats['perCategory'][$slug] ?? 0);
                $progress = $hasScore ? min(100, max(0, $score)) : 0;
                $status = PlayerStatsService::statusLabelFor($score, $hasScore);
            @endphp

            <article class="progress-card reveal">
                <div class="progress-head">
                    <div class="card-icon large">
                        <img
                            src="{{ asset($scenario->image) }}"
                            alt="{{ $scenario->title }}"
                            class="scenario-icon"
                            loading="lazy"
                        >
                    </div>

                    <span class="score-badge">{{ $status }}</span>
                </div>

                <h3>{{ $scenario->title }}</h3>

                <p>{{ $scenario->description }}</p>

                <div class="score-row">
                    <span class="score-label">Best score</span>
                    <span class="score-value">{{ $hasScore ? $score.'/100' : '—' }}</span>
                </div>

                <div
                    class="progress-bar"
                    role="progressbar"
                    aria-valuenow="{{ $progress }}"
                    aria-valuemin="0"
                    aria-valuemax="100"
                    aria-label="{{ $scenario->title }} best score"
                >
                    <span style="width: {{ $progress }}%"></span>
                </div>

                <a href="{{ route('scores.categories') }}"
                   class="btn btn-primary small">
                    View Scores
                </a>
            </article>
        @endforeach
    </section>
@endsection

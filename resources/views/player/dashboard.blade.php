
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
            <a href="{{ route('scores.index') }}" class="btn btn-secondary">My scores</a>
            <a href="{{ route('profile.edit') }}" class="btn btn-secondary">Player profile</a>
            <a href="{{ route('leaderboard.index') }}" class="btn btn-secondary">Leaderboard</a>
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

    <section class="progress-grid" aria-labelledby="scenario-missions-heading">
        <h2 id="scenario-missions-heading" class="visually-hidden">Disaster scenarios</h2>
        @foreach ($stats['scenarios'] as $scenario)
            @php
                $slug = $scenario->slug;
                $hasScore = $stats['hasScore'][$slug] ?? false;
                $score = (int) ($stats['perCategory'][$slug] ?? 0);
                $progress = $hasScore ? min(100, max(0, $score)) : 0;
                $status = PlayerStatsService::statusLabelFor($score, $hasScore);
                // Each catalog scenario carries a hazard class used for its
                // accent rail and progress fill; the mission index is the
                // scenario's fixed position in the catalog.
                $hazardClass = 'hazard-'.match ($slug) {
                    'volcanic-eruption', 'volcano' => 'volcano',
                    default => str_replace('-eruption', '', $slug),
                };
            @endphp

            <article class="progress-card {{ $hazardClass }} reveal">
                <div class="progress-head">
                    <div class="card-icon large">
                        <img
                            src="{{ asset($scenario->image) }}"
                            alt="{{ $scenario->title }}"
                            class="scenario-icon"
                            loading="lazy"
                        >
                    </div>

                    <div class="progress-head-meta">
                        <span class="progress-index">MISSION {{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                        <span class="score-badge {{ \Illuminate\Support\Str::slug($status) }}">{{ $status }}</span>
                        @if ($scenario->difficulty)
                            <span class="difficulty-tag {{ strtolower($scenario->difficulty) }}">{{ $scenario->difficulty }}</span>
                        @endif
                    </div>
                </div>

                <h3>{{ $scenario->title }}</h3>

                <p>{{ $scenario->description }}</p>

                <div class="score-row">
                    <span class="score-label">Best score</span>
                    <span class="score-value">
                        {{ $hasScore ? $score : '—' }}@if ($hasScore)<span class="score-out">/100</span>@endif
                    </span>
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
                   class="btn btn-secondary small">
                    View Scores
                </a>
            </article>
        @endforeach
    </section>
@endsection

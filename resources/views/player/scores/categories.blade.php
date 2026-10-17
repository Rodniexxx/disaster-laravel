@extends('layouts.app')

@section('title', 'Category performance')

@php
    use App\Services\PlayerStatsService;
@endphp

@section('content')
    <section class="dashboard-header reveal">
        <div>
            <x-back-button fallback="scores.index" />
            <p class="eyebrow">Preparedness Center</p>
            <h1>Category performance</h1>
        </div>
    </section>

    @if ($rows->isEmpty())
        <div class="empty-state">No scenarios are available right now.</div>
    @else
        <section class="progress-grid">
            @foreach ($rows as $row)
                @php
                    $scenario = $row['scenario'];
                    $hasScore = (bool) $row['has_score'];
                    $score = (int) $row['score'];
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
                    <p>{{ $scenario->description ?: 'Practice safe response steps.' }}</p>

                    <div class="score-row">
                        <span class="score-label">Best score</span>
                        <span class="score-value">{{ $hasScore ? $score.'/100' : '—' }}</span>
                    </div>
                    <div class="progress-bar">
                        <span style="width: {{ $progress }}%"></span>
                    </div>
                </article>
            @endforeach
        </section>
    @endif
@endsection

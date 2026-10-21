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
        <section class="progress-grid" aria-labelledby="category-performance-heading">
            <h2 id="category-performance-heading" class="visually-hidden">Scenarios</h2>
            @foreach ($rows as $row)
                @php
                    $scenario = $row['scenario'];
                    $hasScore = (bool) $row['has_score'];
                    $score = (int) $row['score'];
                    $progress = $hasScore ? min(100, max(0, $score)) : 0;
                    $status = PlayerStatsService::statusLabelFor($score, $hasScore);
                    $hazardClass = 'hazard-'.match ($scenario->slug) {
                        'volcanic-eruption', 'volcano' => 'volcano',
                        default => str_replace('-eruption', '', $scenario->slug),
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
                    <p>{{ $scenario->description ?: 'Practice safe response steps.' }}</p>

                    <div class="score-row">
                        <span class="score-label">Best score</span>
                        <span class="score-value">
                            {{ $hasScore ? $score : '—' }}@if ($hasScore)<span class="score-out">/100</span>@endif
                        </span>
                    </div>
                    <div class="progress-bar"
                         role="progressbar"
                         aria-valuenow="{{ $progress }}"
                         aria-valuemin="0"
                         aria-valuemax="100"
                         aria-label="{{ $scenario->title }} best score">
                        <span style="width: {{ $progress }}%"></span>
                    </div>
                </article>
            @endforeach
        </section>
    @endif
@endsection

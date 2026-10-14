@extends('layouts.app')

@section('title', 'Category performance')

@php
    use App\Services\PlayerStatsService;
    $emojis = ['earthquake' => '🌎', 'fire' => '🔥', 'typhoon' => '🌧️', 'volcano' => '⛰️'];
@endphp

@section('content')
    <section class="dashboard-header reveal">
        <div>
            <p class="eyebrow">Preparedness Center</p>
            <h1>Category performance</h1>
        </div>
    </section>

    @if ($rows->isEmpty())
        <div class="empty-state">Categories will appear once an administrator adds scenarios.</div>
    @else
        <section class="progress-grid">
            @foreach ($rows as $row)
                @php
                    $score = (int) $row['score'];
                    $progress = min(100, max(0, $score));
                    $status = PlayerStatsService::statusLabel($score);
                @endphp
                <article class="progress-card reveal">
                    <div class="progress-head">
                        <div class="card-icon large">{{ $emojis[strtolower($row['scenario']->slug)] ?? '🌎' }}</div>
                        <span class="score-badge">{{ $status }}</span>
                    </div>
                    <h3>{{ $row['scenario']->title }}</h3>
                    <p>{{ $row['scenario']->description ?: 'Practice safe response steps.' }}</p>

                    <div class="score-row">
                        <span class="score-label">Best score</span>
                        <span class="score-value">{{ $score }}/100</span>
                    </div>
                    <div class="progress-bar">
                        <span style="width: {{ $progress }}%"></span>
                    </div>
                </article>
            @endforeach
        </section>
    @endif
@endsection

@extends('layouts.app')

@section('title', 'Leaderboard')
@section('main-class', 'dashboard-page container leaderboard-page')

@section('content')
    <section class="dashboard-header reveal">
        <div>
            <x-back-button />
            <p class="eyebrow">Top preparedness scores</p>
            <h1>Leaderboard</h1>
        </div>
    </section>

    <section class="leaderboard-controls reveal">
        <div class="category-tabs">
            @foreach ($categories as $tab)
                <a class="tab-btn {{ $tab === $category ? 'active' : '' }}"
                   href="{{ route('leaderboard.index', ['category' => $tab]) }}">{{ $tab }}</a>
            @endforeach
        </div>
    </section>

    <section class="leaderboard-panel reveal">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Rank</th>
                        <th>Player</th>
                        <th>Score</th>
                        <th>Disaster category</th>
                    </tr>
                </thead>
                <tbody>
                    @if ($rows->isEmpty())
                        <tr><td colspan="4" class="table-empty">No scores yet.</td></tr>
                    @else
                        @foreach ($rows as $index => $row)
                            <tr class="leader-row top-{{ $index + 1 }}">
                                <td data-label="Rank">
                                    @if ($index === 0)
                                        <span class="medal medal-gold">🥇</span>
                                    @elseif ($index === 1)
                                        <span class="medal medal-silver">🥈</span>
                                    @elseif ($index === 2)
                                        <span class="medal medal-bronze">🥉</span>
                                    @endif
                                    <span class="rank-text">{{ $row['rank'] }}</span>
                                </td>
                                <td data-label="Player">{{ $row['username'] }}</td>
                                <td data-label="Score">{{ $row['score'] }}</td>
                                <td data-label="Category">{{ $row['category'] }}</td>
                            </tr>
                        @endforeach
                    @endif
                </tbody>
            </table>
        </div>
    </section>
@endsection

@extends('layouts.admin')

@section('title', 'Leaderboard')
@section('heading', 'Leaderboard')
@section('eyebrow', 'Rankings')

@section('back')
    <x-back-button />
@endsection

@section('content')
    <section class="leaderboard-controls">
        <div class="category-tabs">
            @foreach ($categories as $tab)
                <a class="tab-btn {{ $tab === $category ? 'active' : '' }}"
                   href="{{ route('admin.leaderboard', ['category' => $tab]) }}">{{ $tab }}</a>
            @endforeach
        </div>
    </section>

    <p class="muted">Overall totals each player's best completed scenario scores (up to 400). Each disaster is scored out of 100.</p>

    <section class="leaderboard-panel">
        <div class="table-wrap table-compact">
            <table>
                <thead>
                    <tr>
                        <th>Rank</th>
                        <th>Player</th>
                        <th>Readiness score</th>
                        <th class="hide-mobile">Disaster category</th>
                    </tr>
                </thead>
                <tbody>
                    @if ($rows->isEmpty())
                        <tr><td colspan="4" class="table-empty">No readiness scores yet. Complete a scenario to join the rankings.</td></tr>
                    @else
                        @foreach ($rows as $row)
                            <tr class="leader-row top-{{ $row['rank'] }}">
                                <td data-label="Rank">
                                    @if ($row['rank'] === 1)
                                        <span class="medal medal-gold">🥇</span>
                                    @elseif ($row['rank'] === 2)
                                        <span class="medal medal-silver">🥈</span>
                                    @elseif ($row['rank'] === 3)
                                        <span class="medal medal-bronze">🥉</span>
                                    @endif
                                    <span class="rank-text">{{ $row['rank'] }}</span>
                                </td>
                                <td data-label="Player">{{ $row['username'] }}</td>
                                <td data-label="Score">{{ $row['score'] }}</td>
                                <td data-label="Disaster category" class="hide-mobile">{{ $row['category'] }}</td>
                            </tr>
                        @endforeach
                    @endif
                </tbody>
            </table>
        </div>

        <div class="pagination-wrap">
            {{ $rows->links() }}
        </div>
    </section>
@endsection

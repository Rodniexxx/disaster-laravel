@extends('layouts.admin')

@section('title', 'Leaderboard')
@section('heading', 'Leaderboard')
@section('eyebrow', 'Rankings')

@section('content')
    <section class="leaderboard-controls">
        <div class="category-tabs">
            @foreach ($categories as $tab)
                <a class="tab-btn {{ $tab === $category ? 'active' : '' }}"
                   href="{{ route('admin.leaderboard', ['category' => $tab]) }}">{{ $tab }}</a>
            @endforeach
        </div>
    </section>

    <section class="leaderboard-panel">
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
                                <td>
                                    @if ($index === 0)
                                        <span class="medal medal-gold">🥇</span>
                                    @elseif ($index === 1)
                                        <span class="medal medal-silver">🥈</span>
                                    @elseif ($index === 2)
                                        <span class="medal medal-bronze">🥉</span>
                                    @endif
                                    <span class="rank-text">{{ $row['rank'] }}</span>
                                </td>
                                <td>{{ $row['username'] }}</td>
                                <td>{{ $row['score'] }}</td>
                                <td>{{ $row['category'] }}</td>
                            </tr>
                        @endforeach
                    @endif
                </tbody>
            </table>
        </div>
    </section>
@endsection

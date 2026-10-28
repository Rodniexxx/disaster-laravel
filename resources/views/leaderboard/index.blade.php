@extends('layouts.app')

@section('title', 'Leaderboard')
@section('body-class', 'leaderboard-theme')
@section('main-class', 'dashboard-page container leaderboard-page')

@php
    // Vector rank marks keep the podium legible on every platform (emoji
    // glyphs render inconsistently and cannot inherit colour).
    $rankLabel = [
        1 => ['Gold', 'rank-medal--gold'],
        2 => ['Silver', 'rank-medal--silver'],
        3 => ['Bronze', 'rank-medal--bronze'],
    ];
@endphp

@section('content')
    <section class="dashboard-header reveal">
        <div>
            <x-back-button />
            <h1>Leaderboard</h1>
            <p class="section-description">Practice. Improve. Find your place.</p>
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

    <p class="muted leaderboard-note">Overall totals each player's best completed scenario scores (up to 400). Each disaster is scored out of 100.</p>

    <section class="leaderboard-panel reveal">
        <div class="table-wrap table-stack">
            <table>
                <thead>
                    <tr>
                        <th>Rank</th>
                        <th>Player</th>
                        <th>Readiness score</th>
                        <th>Disaster category</th>
                    </tr>
                </thead>
                <tbody>
                    @if ($rows->isEmpty())
                        <tr class="leaderboard-empty-row"><td colspan="4" class="table-empty">No readiness scores yet. Complete a scenario to join the rankings.</td></tr>
                    @else
                        @foreach ($rows as $row)
                            @php
                                $isCurrentPlayer = auth()->check() && $row['username'] === auth()->user()->username;
                            @endphp
                            <tr class="leader-row top-{{ $row['rank'] }} {{ $isCurrentPlayer ? 'is-current' : '' }}">
                                <td data-label="Rank">
                                    @if (isset($rankLabel[$row['rank']]))
                                        <span class="rank-medal {{ $rankLabel[$row['rank']][1] }}"
                                              role="img"
                                              aria-label="{{ $rankLabel[$row['rank']][0] }} — rank {{ $row['rank'] }}">
                                            <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                                                <circle cx="12" cy="15" r="5.25"/>
                                                <path d="M9.4 10.8 6.75 3.9h10.5l-2.65 6.9" fill="none"/>
                                            </svg>
                                        </span>
                                    @endif
                                    <span class="rank-text">{{ $row['rank'] }}</span>
                                </td>
                                <td data-label="Player">
                                    @php($player = $players->get($row['username']))
                                    @if ($player)
                                        <button type="button" class="avatar-person player-profile-trigger"
                                                data-player-profile="{{ route('leaderboard.player', $player) }}"
                                                aria-haspopup="dialog" aria-controls="player-profile-dialog"
                                                aria-label="View {{ $row['username'] }}'s player profile">
                                            <x-player-avatar :user="$player" :size="32" />
                                            <span class="rank-player">{{ $row['username'] }}</span>
                                        </button>
                                    @else
                                        <span class="avatar-person"><x-player-avatar :size="32" /><span class="rank-player">{{ $row['username'] }}</span></span>
                                    @endif
                                    @if ($isCurrentPlayer)
                                        <span class="rank-you">You</span>
                                    @endif
                                </td>
                                <td data-label="Readiness score"><span class="rank-score">{{ $row['score'] }}</span></td>
                                <td data-label="Disaster category">{{ $row['category'] }}</td>
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
    @include('leaderboard.player-profile-modal')
@endsection

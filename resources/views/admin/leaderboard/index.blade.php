@extends('layouts.admin')

@section('title', 'Leaderboard')
@section('heading', 'Leaderboard')
@section('eyebrow', 'Rankings')

@section('back')
    <x-back-button />
@endsection

@section('content')
    @php
        // Overall rows are SUMs across scenarios, so they map to no single
        // record; deletion is only offered where a row = one readiness_progress
        // record (unique on user_id + scenario).
        $isOverall = strcasecmp($category, 'Overall') === 0;
        $columnCount = $isOverall ? 4 : 5;
        $rankLabel = [
            1 => ['Gold', 'rank-medal--gold'],
            2 => ['Silver', 'rank-medal--silver'],
            3 => ['Bronze', 'rank-medal--bronze'],
        ];
    @endphp

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
        <div class="table-wrap table-stack">
            <table>
                <thead>
                    <tr>
                        <th>Rank</th>
                        <th>Player</th>
                        <th>Readiness score</th>
                        <th>Disaster category</th>
                        @unless ($isOverall)
                            <th>Actions</th>
                        @endunless
                    </tr>
                </thead>
                <tbody>
                    @if ($rows->isEmpty())
                        <tr class="leaderboard-empty-row"><td colspan="{{ $columnCount }}" class="table-empty">No readiness scores yet. Complete a scenario to join the rankings.</td></tr>
                    @else
                        @foreach ($rows as $row)
                            <tr class="leader-row top-{{ $row['rank'] }}">
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
                                    <span class="avatar-person"><x-player-avatar :url="$avatars[$row['username']] ?? null" :size="32" /><span class="rank-player">{{ $row['username'] }}</span></span>
                                </td>
                                <td data-label="Readiness score"><span class="rank-score">{{ $row['score'] }}</span></td>
                                <td data-label="Disaster category">{{ $row['category'] }}</td>
                                @unless ($isOverall)
                                    <td class="table-actions" data-label="Actions">
                                        <button type="button"
                                                class="btn small btn-danger score-delete-btn"
                                                data-delete-score
                                                data-user-id="{{ $userIds[$row['username']] ?? '' }}"
                                                data-username="{{ $row['username'] }}"
                                                data-category="{{ $row['category'] }}"
                                                data-score="{{ $row['score'] }}"
                                                aria-label="Delete {{ $row['username'] }}'s {{ $row['category'] }} score">
                                            Delete
                                        </button>
                                    </td>
                                @endunless
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

    @unless ($isOverall)
        <dialog class="score-delete-dialog"
                id="score-delete-dialog"
                aria-labelledby="score-delete-dialog-title"
                aria-describedby="score-delete-dialog-desc">
            <form method="POST" action="{{ route('admin.leaderboard.destroy') }}" id="score-delete-form">
                @csrf
                @method('DELETE')
                <input type="hidden" name="user_id" id="score-delete-user-id" value="">
                <input type="hidden" name="scenario" id="score-delete-scenario" value="">
                <input type="hidden" name="category" value="{{ $category }}">

                <h2 id="score-delete-dialog-title">Delete this leaderboard score?</h2>

                <p class="dialog-text" id="score-delete-dialog-desc">
                    You are about to permanently delete <strong id="score-delete-player"></strong>'s
                    personal-best readiness score of <strong id="score-delete-score"></strong>
                    for <strong id="score-delete-category"></strong>.
                </p>

                <p class="dialog-note">
                    Only that one score record is removed — the player's account, their scores for
                    the other disaster categories, and their attempt history all stay intact. Their
                    Overall total and the rankings are recalculated without it.
                </p>

                <div class="dialog-actions">
                    <button type="button" class="btn small btn-secondary" data-dialog-cancel autofocus>Cancel</button>
                    <button type="submit" class="btn small btn-danger" id="score-delete-confirm">Confirm delete</button>
                </div>
            </form>
        </dialog>

        <script>
            (function () {
                var dialog = document.getElementById('score-delete-dialog');
                if (!dialog || typeof dialog.showModal !== 'function') return;

                var form = document.getElementById('score-delete-form');
                var userIdInput = document.getElementById('score-delete-user-id');
                var scenarioInput = document.getElementById('score-delete-scenario');
                var playerEl = document.getElementById('score-delete-player');
                var scoreEl = document.getElementById('score-delete-score');
                var categoryEl = document.getElementById('score-delete-category');
                var confirmBtn = document.getElementById('score-delete-confirm');
                var lastTrigger = null;

                document.querySelectorAll('[data-delete-score]').forEach(function (button) {
                    button.addEventListener('click', function () {
                        lastTrigger = button;
                        userIdInput.value = button.dataset.userId;
                        // Display name; the server resolves it (name/slug/alias)
                        // to the canonical scenario before deleting anything.
                        scenarioInput.value = button.dataset.category;
                        playerEl.textContent = button.dataset.username;
                        scoreEl.textContent = button.dataset.score;
                        categoryEl.textContent = button.dataset.category;
                        confirmBtn.disabled = false;
                        confirmBtn.textContent = 'Confirm delete';
                        dialog.showModal();
                    });
                });

                dialog.querySelector('[data-dialog-cancel]').addEventListener('click', function () {
                    dialog.close('cancelled');
                });

                // Clicking the backdrop (outside the panel) cancels.
                dialog.addEventListener('click', function (event) {
                    var box = dialog.getBoundingClientRect();
                    var inside = event.clientX >= box.left && event.clientX <= box.right
                        && event.clientY >= box.top && event.clientY <= box.bottom;
                    if (!inside) dialog.close('cancelled');
                });

                // ESC closes natively (cancel -> close); return focus to the row.
                dialog.addEventListener('close', function () {
                    if (lastTrigger) lastTrigger.focus();
                });

                // Lock the confirm button so a double click cannot submit twice.
                form.addEventListener('submit', function () {
                    confirmBtn.disabled = true;
                    confirmBtn.textContent = 'Deleting…';
                });
            })();
        </script>
    @endunless
@endsection

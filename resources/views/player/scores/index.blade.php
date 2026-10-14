@extends('layouts.app')

@section('title', 'My scores')

@section('content')
    <section class="dashboard-header reveal">
        <div>
            <p class="eyebrow">Preparedness Center</p>
            <h1>My scores</h1>
        </div>
        <div class="dashboard-header-actions">
            <span class="welcome-tag">{{ $scores->count() }} {{ $scores->count() === 1 ? 'category' : 'categories' }}</span>
        </div>
    </section>

    <section class="admin-panel reveal">
        <div class="panel-header">
            <h2>Saved scores</h2>
        </div>

        @if ($scores->isEmpty())
            <div class="empty-state">No scores to show. Complete a scenario in the MANGNO game and your scores will appear here.</div>
        @else
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Category</th>
                            <th>Best score</th>
                            <th>Progress</th>
                            <th>Last updated</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($scores as $score)
                            @php $progress = min(100, max(0, (int) $score->score)); @endphp
                            <tr>
                                <td>{{ $score->category }}</td>
                                <td>{{ (int) $score->score }}/100</td>
                                <td>
                                    <div class="progress-bar">
                                        <span style="width: {{ $progress }}%"></span>
                                    </div>
                                </td>
                                <td>{{ $score->created_at?->format('M j, Y') ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
@endsection

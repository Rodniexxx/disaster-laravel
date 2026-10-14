@extends('layouts.admin')

@section('title', 'Player Progress')
@section('heading', 'Player Progress')
@section('eyebrow', 'Player Insights')

@section('content')
    <section class="admin-panel">
        <div class="panel-header">
            <h2>Filter Progress</h2>
        </div>
        <form method="get" class="admin-filter-form">
            <div class="form-grid compact">
                <div class="field-group">
                    <label for="search">Search</label>
                    <input type="text" id="search" name="search" value="{{ $search }}" placeholder="Username, email, scenario">
                </div>
                <div class="field-group">
                    <label for="status">Status</label>
                    <select id="status" name="status">
                        <option value="all" @selected($status === 'all')>All</option>
                        <option value="not_started" @selected($status === 'not_started')>Not Started</option>
                        <option value="in_progress" @selected($status === 'in_progress')>In Progress</option>
                        <option value="completed" @selected($status === 'completed')>Completed</option>
                    </select>
                </div>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary small">Apply Filters</button>
            </div>
        </form>
    </section>

    <section class="admin-panel">
        <div class="panel-header">
            <h2>Progress Records</h2>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Player</th>
                        <th>Scenario</th>
                        <th>Status</th>
                        <th>Score</th>
                        <th>Updated</th>
                    </tr>
                </thead>
                <tbody>
                    @if ($rows->isEmpty())
                        <tr><td colspan="5" class="table-empty">No progress records found.</td></tr>
                    @else
                        @foreach ($rows as $row)
                            <tr>
                                <td>{{ $row->user?->username ?? 'Unknown' }}</td>
                                <td>{{ ucfirst($row->scenario_slug) }}</td>
                                <td><span class="status-badge {{ $row->status }}">{{ $row->status }}</span></td>
                                <td>{{ (int) $row->score }}</td>
                                <td>{{ $row->updated_at?->format('M j, Y H:i') }}</td>
                            </tr>
                        @endforeach
                    @endif
                </tbody>
            </table>
        </div>

        {{ $rows->links() }}
    </section>
@endsection

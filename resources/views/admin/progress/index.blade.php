@extends('layouts.admin')

@section('title', 'Player Progress')
@section('heading', 'Player Progress')
@section('eyebrow', 'Player Insights')
@section('back')
    <x-back-button />
@endsection

@section('content')
    <section class="admin-panel">
        <div class="panel-header"><h2>Filter players</h2></div>
        <form method="get" class="admin-filter-form">
            <div class="form-grid compact">
                <div class="field-group">
                    <label for="search">Player username</label>
                    <input type="search" id="search" name="search" value="{{ $search }}" maxlength="120" placeholder="Search username">
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
            <div class="form-actions"><button type="submit" class="btn btn-primary small">Apply Filters</button></div>
        </form>
    </section>
    <section class="admin-panel" data-player-progress data-feed-url="{{ route('admin.progress.data', request()->only('search', 'status', 'page')) }}">
        <div class="panel-header"><h2>Player progress</h2><p class="muted" data-progress-connection role="status">Updates automatically every 15 seconds.</p></div>
        <p class="muted">Each completed disaster contributes 25%. Activity reflects recorded gameplay events, not whether a player is currently online.</p>
        <div data-progress-results>@include('admin.progress.results')</div>
    </section>
@endsection

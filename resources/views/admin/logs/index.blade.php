@extends('layouts.admin')

@section('title', 'Activity Log')
@section('heading', 'Activity Logs')
@section('eyebrow', 'System Activity')

@section('back')
    <x-back-button />
@endsection

@section('content')
    <section class="admin-panel">
        <div class="panel-header">
            <h2>Recent Admin Activity</h2>
        </div>
        <div class="table-wrap table-stack">
            <table>
                <thead>
                    <tr>
                        <th>Time</th>
                        <th>Admin</th>
                        <th>Action</th>
                        <th>Resource</th>
                        <th>Details</th>
                    </tr>
                </thead>
                <tbody>
                    @if ($logs->isEmpty())
                        <tr><td colspan="5" class="table-empty">No activity logged yet.</td></tr>
                    @else
                        @foreach ($logs as $log)
                            <tr>
                                <td data-label="Time">{{ $log->created_at?->format('M j, Y H:i') }}</td>
                                <td data-label="Admin">{{ $log->admin?->username ?? 'Unknown' }}</td>
                                <td data-label="Action">{{ $log->action }}</td>
                                <td data-label="Resource">{{ $log->resource }}</td>
                                <td data-label="Details">
                                    @if (! empty($log->details))
                                        @foreach ($log->details as $key => $value)
                                            {{ $key }}: {{ is_scalar($value) ? $value : json_encode($value) }}@if (! $loop->last), @endif
                                        @endforeach
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    @endif
                </tbody>
            </table>
        </div>

        {{ $logs->links() }}
    </section>
@endsection

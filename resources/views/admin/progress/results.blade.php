<div class="table-wrap table-stack">
    <table class="player-progress-table">
        <thead><tr><th>Player</th><th>Completed scenarios</th><th>Progress</th><th>Status</th><th>Last activity</th></tr></thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td data-label="Player"><span class="avatar-person"><x-player-avatar :url="$row['profile_picture_url']" :size="32" /><strong>{{ $row['username'] }}</strong></span></td>
                    <td data-label="Completed scenarios"><div><strong>{{ $row['completed_scenarios'] }}/4</strong><span class="progress-detail">{{ implode(', ', $row['completed_names']) ?: 'None yet' }}</span></div></td>
                    <td data-label="Progress"><div><span class="progress-percentage">{{ $row['progress_percentage'] }}%</span><progress max="100" value="{{ $row['progress_percentage'] }}" aria-label="{{ $row['username'] }} scenario completion">{{ $row['progress_percentage'] }}%</progress></div></td>
                    <td data-label="Status"><span class="status-badge {{ $row['status'] }}">{{ $row['status_label'] }}</span></td>
                    <td data-label="Last activity">@if ($row['last_activity_at'])<time datetime="{{ $row['last_activity_at'] }}">{{ \Illuminate\Support\Carbon::parse($row['last_activity_at'])->format('M j, Y H:i T') }}</time>@else<span class="muted">None recorded</span>@endif</td>
                </tr>
            @empty
                <tr><td colspan="5" class="table-empty">No players match these filters.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
{{ $rows->links() }}

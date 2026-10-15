@extends('layouts.admin')

@section('title', 'Game Versions')
@section('heading', 'Game Versions')
@section('eyebrow', 'Game Release')

@section('back')
    <x-back-button />
@endsection

@section('content')
    <section class="admin-panel">
        <div class="panel-header">
            <h2>{{ $editing ? 'Edit Version' : 'Create Version' }}</h2>
        </div>
        <form method="post" action="{{ $editing ? route('admin.versions.update', $editing) : route('admin.versions.store') }}" class="admin-form" data-unsaved-guard>
            @csrf
            @if ($editing)
                @method('PUT')
            @endif

            <div class="form-grid">
                <div class="field-group">
                    <label for="version_number">Version Number</label>
                    <input type="text" id="version_number" name="version_number" value="{{ old('version_number', $editing->version_number ?? '') }}" required>
                </div>
                <div class="field-group">
                    <label for="platform">Platform</label>
                    <input type="text" id="platform" name="platform" value="{{ old('platform', $editing->platform ?? 'Windows') }}">
                </div>
                <div class="field-group">
                    <label for="status">Status</label>
                    <select id="status" name="status">
                        <option value="active" @selected(old('status', $editing->status ?? 'active') === 'active')>Active</option>
                        <option value="inactive" @selected(old('status', $editing->status ?? 'active') === 'inactive')>Inactive</option>
                    </select>
                </div>
            </div>

            <div class="field-group">
                <label for="release_notes">Release Notes</label>
                <textarea id="release_notes" name="release_notes" rows="4">{{ old('release_notes', $editing->release_notes ?? '') }}</textarea>
            </div>

            <div class="form-grid">
                <div class="field-group">
                    <label for="release_date">Release Date</label>
                    <input type="datetime-local" id="release_date" name="release_date"
                           value="{{ old('release_date', optional($editing->release_date ?? null)->format('Y-m-d\TH:i')) }}">
                </div>
                <div class="field-group">
                    <label for="download_url">Download/Update URL</label>
                    <input type="url" id="download_url" name="download_url" value="{{ old('download_url', $editing->download_url ?? '') }}">
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">{{ $editing ? 'Save Changes' : 'Create Version' }}</button>
                @if ($editing)
                    <a href="{{ route('admin.versions.index') }}" class="btn btn-secondary">Cancel</a>
                @endif
            </div>
        </form>
    </section>

    <section class="admin-panel">
        <div class="panel-header">
            <h2>Version List</h2>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Version</th>
                        <th>Platform</th>
                        <th>Status</th>
                        <th>Release Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @if ($versions->isEmpty())
                        <tr><td colspan="5" class="table-empty">No versions found.</td></tr>
                    @else
                        @foreach ($versions as $version)
                            <tr>
                                <td data-label="Version">{{ $version->version_number }}</td>
                                <td data-label="Platform">{{ $version->platform }}</td>
                                <td data-label="Status"><span class="status-badge {{ $version->status }}">{{ $version->status }}</span></td>
                                <td data-label="Release date">{{ $version->release_date?->format('M j, Y H:i') ?? 'Not scheduled' }}</td>
                                <td class="table-actions" data-label="Actions">
                                    @if ($version->download_url)
                                        <a href="{{ $version->download_url }}" target="_blank" rel="noopener" class="btn btn-secondary small">Download</a>
                                    @endif
                                    <a href="{{ route('admin.versions.index', ['edit' => $version->id]) }}" class="btn btn-secondary small">Edit</a>
                                    <form method="post" action="{{ route('admin.versions.destroy', $version) }}" class="inline-form" onsubmit="return confirm('Delete this version?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger small">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    @endif
                </tbody>
            </table>
        </div>
    </section>
@endsection

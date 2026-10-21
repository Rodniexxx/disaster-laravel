@extends('layouts.admin')

@section('title', 'Announcements')
@section('heading', 'Announcements')
@section('eyebrow', 'Website Content')

@section('back')
    <x-back-button />
@endsection

@section('content')
    <section class="admin-panel">
        <div class="panel-header">
            <h2>{{ $editing ? 'Edit Announcement' : 'Create Announcement' }}</h2>
        </div>
        <form method="post" action="{{ $editing ? route('admin.announcements.update', $editing) : route('admin.announcements.store') }}" class="admin-form" data-unsaved-guard>
            @csrf
            @if ($editing)
                @method('PUT')
            @endif

            <div class="form-grid">
                <div class="field-group">
                    <label for="title">Title</label>
                    <input type="text" id="title" name="title" value="{{ old('title', $editing->title ?? '') }}" required>
                </div>
                <div class="field-group">
                    <label for="publish_date">Publish Date</label>
                    <input type="datetime-local" id="publish_date" name="publish_date"
                           value="{{ old('publish_date', optional($editing->publish_date ?? null)->format('Y-m-d\TH:i')) }}">
                </div>
                <div class="field-group">
                    <label for="status">Status</label>
                    <select id="status" name="status">
                        <option value="draft" @selected(old('status', $editing->status ?? 'draft') === 'draft')>Draft</option>
                        <option value="published" @selected(old('status', $editing->status ?? 'draft') === 'published')>Published</option>
                        <option value="archived" @selected(old('status', $editing->status ?? 'draft') === 'archived')>Archived</option>
                    </select>
                </div>
            </div>

            <div class="field-group">
                <label for="message">Message</label>
                <textarea id="message" name="message" rows="4" required>{{ old('message', $editing->message ?? '') }}</textarea>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">{{ $editing ? 'Save Changes' : 'Create Announcement' }}</button>
                @if ($editing)
                    <a href="{{ route('admin.announcements.index') }}" class="btn btn-secondary">Cancel</a>
                @endif
            </div>
        </form>
    </section>

    <section class="admin-panel">
        <div class="panel-header">
            <h2>Announcement List</h2>
        </div>
        <div class="table-wrap table-stack">
            <table>
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Status</th>
                        <th>Publish Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @if ($announcements->isEmpty())
                        <tr><td colspan="4" class="table-empty">No announcements found.</td></tr>
                    @else
                        @foreach ($announcements as $announcement)
                            <tr>
                                <td data-label="Title">{{ $announcement->title }}</td>
                                <td data-label="Status"><span class="status-badge {{ $announcement->status }}">{{ $announcement->status }}</span></td>
                                <td data-label="Publish date">{{ $announcement->publish_date?->format('M j, Y H:i') ?? 'Not scheduled' }}</td>
                                <td class="table-actions" data-label="Actions">
                                    <a href="{{ route('admin.announcements.index', ['edit' => $announcement->id]) }}" class="btn btn-secondary small">Edit</a>
                                    <form method="post" action="{{ route('admin.announcements.destroy', $announcement) }}" class="inline-form" onsubmit="return confirm('Delete this announcement?');">
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

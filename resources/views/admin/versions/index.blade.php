@extends('layouts.admin')

@section('title', 'Game Versions')
@section('heading', 'Game Version Management')
@section('eyebrow', 'Game Release')

@section('back')
    <x-back-button />
@endsection

@section('content')
    <section class="admin-panel">
        <div class="panel-header">
            <h2>Download Page Content</h2>
        </div>
        <form method="post" action="{{ route('admin.versions.content') }}" class="admin-form" data-unsaved-guard>
            @csrf
            @method('PUT')

          

            <div class="form-grid">
                <div class="field-group">
                    <label for="download_section_eyebrow">Downloads Section Eyebrow</label>
                    <input type="text" id="download_section_eyebrow" name="download_section_eyebrow" value="{{ old('download_section_eyebrow', $settings['download_section_eyebrow'] ?? '') }}">
                </div>
                <div class="field-group">
                    <label for="download_section_heading">Downloads Section Heading</label>
                    <input type="text" id="download_section_heading" name="download_section_heading" value="{{ old('download_section_heading', $settings['download_section_heading'] ?? '') }}">
                </div>
            </div>

            <div class="form-grid">
                <div class="field-group">
                    <label for="download_windows_title">Windows Card Title</label>
                    <input type="text" id="download_windows_title" name="download_windows_title" value="{{ old('download_windows_title', $settings['download_windows_title'] ?? '') }}">
                    <textarea id="download_windows_description" name="download_windows_description" rows="2" aria-label="Windows card description">{{ old('download_windows_description', $settings['download_windows_description'] ?? '') }}</textarea>
                </div>
                <div class="field-group">
                    <label for="download_android_title">Android Card Title</label>
                    <input type="text" id="download_android_title" name="download_android_title" value="{{ old('download_android_title', $settings['download_android_title'] ?? '') }}">
                    <textarea id="download_android_description" name="download_android_description" rows="2" aria-label="Android card description">{{ old('download_android_description', $settings['download_android_description'] ?? '') }}</textarea>
                </div>
            </div>

            <div class="form-grid">
                <div class="field-group">
                    <label for="download_before_heading">"Before You Download" Heading</label>
                    <input type="text" id="download_before_heading" name="download_before_heading" value="{{ old('download_before_heading', $settings['download_before_heading'] ?? '') }}">
                </div>
                <div class="field-group">
                    <label for="download_extra_heading">Extra Section Heading <span class="field-help">(optional)</span></label>
                    <input type="text" id="download_extra_heading" name="download_extra_heading" value="{{ old('download_extra_heading', $settings['download_extra_heading'] ?? '') }}">
                </div>
            </div>

            <div class="field-group">
                <label for="download_before_notes">Before You Download Instructions</label>
                <textarea id="download_before_notes" name="download_before_notes" rows="6">{{ old('download_before_notes', $settings['download_before_notes'] ?? '') }}</textarea>
                <p class="field-help">One instruction per line.</p>
            </div>

            <div class="field-group">
                <label for="download_about">About MANGNO</label>
                <textarea id="download_about" name="download_about" rows="4">{{ old('download_about', $settings['download_about'] ?? '') }}</textarea>
                <p class="field-help">Leave empty to hide the About section.</p>
            </div>

            <div class="field-group">
                <label for="download_extra">Extra Information <span class="field-help">(optional)</span></label>
                <textarea id="download_extra" name="download_extra" rows="3">{{ old('download_extra', $settings['download_extra'] ?? '') }}</textarea>
                <p class="field-help">Leave empty to hide the extra section.</p>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Save Page Content</button>
            </div>
        </form>
    </section>

    <section class="admin-panel" id="release-form">
        <div class="panel-header">
            <h2>{{ $editing ? 'Edit Release' : 'Add Release' }}</h2>
            @if ($editing)
                <a href="{{ route('admin.versions.index') }}" class="btn btn-secondary small">Cancel Edit</a>
            @endif
        </div>

        <form method="post"
              action="{{ $editing ? route('admin.versions.update', $editing) : route('admin.versions.store') }}"
              class="admin-form" data-unsaved-guard>
            @csrf
            @if ($editing)
                @method('PUT')
            @endif

            <div class="form-grid">
                <div class="field-group">
                    <label for="platform">Platform</label>
                    <select id="platform" name="platform" required>
                        @foreach ($platforms as $key => $definition)
                            <option value="{{ $key }}" @selected(old('platform', $editing ? strtolower($editing->platform) : ($prefillPlatform ?? 'windows')) === $key)>
                                {{ $definition['name'] ?? ucfirst($key) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="field-group">
                    <label for="version_number">Version Number</label>
                    <input type="text" id="version_number" name="version_number" value="{{ old('version_number', $editing->version_number ?? '') }}" required>
                </div>
                <div class="field-group">
                    <label for="release_title">Release Title <span class="field-help">(optional)</span></label>
                    <input type="text" id="release_title" name="release_title" value="{{ old('release_title', $editing->release_title ?? '') }}">
                </div>
                <div class="field-group">
                    <label for="status">Publication Status</label>
                    <select id="status" name="status">
                        <option value="active" @selected(old('status', $editing->status ?? 'active') === 'active')>Published</option>
                        <option value="inactive" @selected(old('status', $editing->status ?? 'active') === 'inactive')>Unpublished</option>
                    </select>
                </div>
                <div class="field-group">
                    <label for="release_date">Release Date</label>
                    <input type="datetime-local" id="release_date" name="release_date"
                           value="{{ old('release_date', optional($editing->release_date ?? null)->format('Y-m-d\TH:i')) }}">
                </div>
                <div class="field-group">
                    <label for="requirements">Minimum System Requirements <span class="field-help">(optional)</span></label>
                    <input type="text" id="requirements" name="requirements" value="{{ old('requirements', $editing->requirements ?? '') }}" placeholder="e.g. Windows 10 (64-bit), 4 GB RAM">
                </div>
            </div>

            <div class="field-group">
                <label for="download_url">External Download URL <span class="field-help">(optional)</span></label>
                <input type="url" id="download_url" name="download_url" value="{{ old('download_url', $editing->download_url ?? '') }}" placeholder="https://...">
                <p class="field-help">Users download the game from this external URL.</p>
            </div>

            <div class="form-grid">
                <div class="field-group">
                    <label for="file_name">File Name <span class="field-help">(optional)</span></label>
                    <input type="text" id="file_name" name="file_name" maxlength="255" value="{{ old('file_name', $editing->file_name ?? '') }}" placeholder="e.g. MANGNO-Windows.exe">
                </div>
                <div class="field-group">
                    <label for="file_size">File Size (bytes) <span class="field-help">(optional)</span></label>
                    <input type="number" id="file_size" name="file_size" min="0" step="1" value="{{ old('file_size', $editing->file_size ?? '') }}" aria-describedby="file_size_help">
                    <p class="field-help" id="file_size_help">Enter the size in bytes (1 MB = 1,048,576 bytes). The download page displays KB, MB or GB automatically.</p>
                </div>
            </div>

            <div class="field-group">
                <label for="release_notes">Release Notes / Description</label>
                <textarea id="release_notes" name="release_notes" rows="4">{{ old('release_notes', $editing->release_notes ?? '') }}</textarea>
            </div>

            <label class="checkbox-field">
                <input type="checkbox" name="is_latest" value="1" @checked(old('is_latest', $editing->is_latest ?? false))>
                <span>Mark as the latest release for this platform</span>
            </label>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">{{ $editing ? 'Save Changes' : 'Add Release' }}</button>
                @if ($editing)
                    <a href="{{ route('admin.versions.index') }}" class="btn btn-secondary">Cancel</a>
                @endif
            </div>
        </form>
    </section>

    @foreach ($platforms as $platformKey => $definition)
        @php
            $rows = $versions->filter(fn ($version) => strtolower($version->platform) === $platformKey)->values();
        @endphp
        <section class="admin-panel">
            <div class="panel-header">
                <h2>{{ $definition['name'] ?? ucfirst($platformKey) }} Releases</h2>
                <a href="{{ route('admin.versions.index', ['platform' => $platformKey]) }}#release-form" class="btn btn-secondary small">
                    Add {{ $definition['name'] ?? ucfirst($platformKey) }} Release
                </a>
            </div>
            <div class="table-wrap table-stack">
                <table>
                    <thead>
                        <tr>
                            <th>Version</th>
                            <th>Status</th>
                            <th>Latest</th>
                            <th>Build</th>
                            <th>Release Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if ($rows->isEmpty())
                            <tr><td colspan="6" class="table-empty">No {{ strtolower($definition['name'] ?? $platformKey) }} releases yet.</td></tr>
                        @else
                            @foreach ($rows as $version)
                                <tr>
                                    <td data-label="Version">
                                        <strong>{{ $version->version_number }}</strong>
                                        @if ($version->release_title)
                                            <br><span class="release-meta">{{ $version->release_title }}</span>
                                        @endif
                                    </td>
                                    <td data-label="Status">
                                        <span class="status-badge {{ $version->status }}">{{ $version->isPublished() ? 'Published' : 'Unpublished' }}</span>
                                    </td>
                                    <td data-label="Latest">
                                        @if ($version->is_latest)
                                            <span class="status-badge active">Latest</span>
                                        @else
                                            <span class="release-meta">&mdash;</span>
                                        @endif
                                    </td>
                                    <td data-label="Build">
                                        @if ($version->download_url)
                                            <span class="release-file-name">{{ $version->download_url }}</span>
                                        @else
                                            <span class="release-meta">No external URL set</span>
                                        @endif
                                    </td>
                                    <td data-label="Release date">{{ $version->release_date?->format('M j, Y H:i') ?? 'Not set' }}</td>
                                    <td class="table-actions" data-label="Actions">
                                        @if ($version->isAvailable())
                                            <a href="{{ $version->download_url }}" class="btn btn-secondary small">Download</a>
                                        @endif
                                        <a href="{{ route('admin.versions.index', ['edit' => $version->id]) }}#release-form" class="btn btn-secondary small">Edit</a>
                                        <form method="post" action="{{ route('admin.versions.toggle', $version) }}" class="inline-form">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="btn btn-secondary small">{{ $version->isPublished() ? 'Unpublish' : 'Publish' }}</button>
                                        </form>
                                        @unless ($version->is_latest)
                                            <form method="post" action="{{ route('admin.versions.latest', $version) }}" class="inline-form">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="btn btn-secondary small">Set Latest</button>
                                            </form>
                                        @endunless
                                        <form method="post" action="{{ route('admin.versions.destroy', $version) }}" class="inline-form" onsubmit="return confirm('Delete this release?');">
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
    @endforeach

    <section class="admin-panel">
        <div class="panel-header">
            <h2>How Downloads Work</h2>
        </div>
        <p class="field-help">
            Users download the game from the external URL set on each release. The public
            <a href="{{ route('download') }}" target="_blank" rel="noopener">/download</a>
            page always shows the latest published release that has an available external URL.
        </p>
    </section>
@endsection

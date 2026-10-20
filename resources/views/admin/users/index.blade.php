@extends('layouts.admin')

@section('title', 'User Management')
@section('heading', 'Players / Users')
@section('eyebrow', 'User Management')
@section('back')
    <x-back-button />
@endsection

@php
    $roles = ['user' => 'Player', 'admin' => 'Administrator'];
@endphp

@section('content')
    <section class="admin-panel">
        <div class="panel-header">
            <h2>{{ $editing ? 'Edit User' : 'Create User' }}</h2>
        </div>
        <form method="post" action="{{ $editing ? route('admin.users.update', $editing) : route('admin.users.store') }}" class="admin-form" data-unsaved-guard>
            @csrf
            @if ($editing)
                @method('PUT')
            @endif

            <div class="form-grid">
                <div class="field-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" value="{{ old('username', $editing->username ?? '') }}" required>
                </div>
                <div class="field-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" value="{{ old('email', $editing->email ?? '') }}" required>
                </div>
                <div class="field-group">
                    <label for="role">Role</label>
                    <select id="role" name="role">
                        @foreach ($roles as $value => $label)
                            <option value="{{ $value }}" @selected(old('role', $editing->role ?? 'user') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="form-grid">
                <div class="field-group">
                    <label for="password">Password{{ $editing ? ' (leave blank to keep current)' : '' }}</label>
                    <input type="password" id="password" name="password" autocomplete="new-password" {{ $editing ? '' : 'required' }}>
                </div>
                <div class="field-group">
                    <label for="password_confirmation">Confirm Password</label>
                    <input type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password" {{ $editing ? '' : 'required' }}>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">{{ $editing ? 'Save Changes' : 'Create User' }}</button>
                @if ($editing)
                    <a href="{{ route('admin.users.index') }}" class="btn btn-secondary">Cancel</a>
                @endif
            </div>
        </form>
    </section>

    <section class="admin-panel">
        <div class="panel-header">
            <h2>Search and Filter</h2>
        </div>
        <form method="get" class="admin-filter-form">
            <div class="form-grid compact">
                <div class="field-group">
                    <label for="search">Search</label>
                    <input type="text" id="search" name="search" value="{{ $search }}" placeholder="Username or email">
                </div>
                <div class="field-group">
                    <label for="status">Status</label>
                    <select id="status" name="status">
                        <option value="all" @selected($status === 'all')>All</option>
                        <option value="active" @selected($status === 'active')>Active</option>
                        <option value="inactive" @selected($status === 'inactive')>Inactive</option>
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
            <h2>User Directory</h2>
        </div>
        <div class="table-wrap table-stack">
            <table>
                <thead>
                    <tr>
                        <th>Username</th>
                        <th class="hide-tablet">Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th class="hide-tablet">Joined</th>
                        <th class="hide-mobile">Completed</th>
                        <th class="hide-mobile">Sessions</th>
                        <th class="hide-mobile">Best Score</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @if ($users->isEmpty())
                        <tr><td colspan="9" class="table-empty">No users found.</td></tr>
                    @else
                        @foreach ($users as $user)
                            @php $meta = $sessions->get($user->id); @endphp
                            <tr>
                                <td data-label="Username">{{ $user->username }}</td>
                                <td data-label="Email" class="hide-tablet">{{ $user->email }}</td>
                                <td data-label="Role"><span class="status-badge {{ $user->role }}">{{ $user->role }}</span></td>
                                <td data-label="Status"><span class="status-badge {{ $user->isActive() ? 'active' : 'inactive' }}">{{ $user->isActive() ? 'Active' : 'Inactive' }}</span></td>
                                <td data-label="Joined" class="hide-tablet">{{ $user->created_at?->format('M j, Y') }}</td>
                                <td data-label="Completed" class="hide-mobile">{{ (int) $completed->get($user->id, 0) }}</td>
                                <td data-label="Sessions" class="hide-mobile">{{ (int) ($meta->sessions ?? 0) }}</td>
                                <td data-label="Best score" class="hide-mobile">{{ (int) ($meta->best_score ?? 0) }}</td>
                                <td class="table-actions" data-label="Actions">
                                    <a href="{{ request()->fullUrlWithQuery(['edit' => $user->id]) }}" class="btn btn-secondary small">Edit</a>
                                    <form method="post" action="{{ route('admin.users.toggle-status', $user) }}" class="inline-form">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="btn btn-secondary small">{{ $user->isActive() ? 'Disable' : 'Enable' }}</button>
                                    </form>
                                    @if ($user->id !== auth()->id())
                                        <form method="post" action="{{ route('admin.users.destroy', $user) }}" class="inline-form" onsubmit="return confirm('Delete this user?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger small">Delete</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    @endif
                </tbody>
            </table>
        </div>

        {{ $users->links() }}
    </section>
@endsection

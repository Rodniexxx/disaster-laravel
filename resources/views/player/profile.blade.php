@extends('layouts.app')

@section('title', 'Profile & settings')

@section('content')
    @php
        $user = auth()->user();
    @endphp

    <section class="dashboard-header reveal">
        <div>
            <x-back-button />
            <p class="eyebrow">Preparedness Center</p>
            <h1>Profile &amp; settings</h1>
        </div>
        <div class="dashboard-header-actions">
            <span class="welcome-tag">{{ ucfirst($user->role) }}</span>
        </div>
    </section>

    <div class="admin-grid">
        <article class="admin-panel">
            <div class="panel-header">
                <h2>Account summary</h2>
            </div>
            <div class="info-stack">
                <div class="avatar-person profile-picture-summary"><x-player-avatar :user="$user" :size="72" /><strong>{{ $user->username }}</strong></div>
                <div class="kpi-row">
                    <span>Username</span>
                    <strong>{{ $user->username }}</strong>
                </div>
                <div class="kpi-row">
                    <span>Email</span>
                    <strong>{{ $user->email }}</strong>
                </div>
                <div class="kpi-row">
                    <span>Role</span>
                    <strong>{{ ucfirst($user->role) }}</strong>
                </div>
                <div class="kpi-row">
                    <span>Status</span>
                    <strong>{{ $user->isActive() ? 'Active' : 'Inactive' }}</strong>
                </div>
                <div class="kpi-row">
                    <span>Member since</span>
                    <strong>{{ $user->created_at?->format('M j, Y') ?? '—' }}</strong>
                </div>
                <div class="kpi-row">
                    <span>Last sign-in</span>
                    <strong>{{ $user->last_login_at?->format('M j, Y g:i A') ?? '—' }}</strong>
                </div>
            </div>
        </article>

        <article class="admin-panel">
            <div class="panel-header"><h2>Profile picture</h2></div>
            <form method="POST" action="{{ route('profile-picture.update') }}" enctype="multipart/form-data" class="admin-form profile-picture-form" data-unsaved-guard>
                @csrf
                @method('PATCH')
                <input type="hidden" name="expected_revision" value="{{ $user->playerProfile?->revision ?? 0 }}">
                @include('partials.avatar-upload', ['avatarUser' => $user])
                <div class="form-actions"><button type="submit" class="btn btn-primary">Save picture</button></div>
            </form>
            <div class="panel-header">
                <h2>Account details</h2>
            </div>
            <p class="muted">Your username is shown on the leaderboard.</p>

            <form method="POST" action="{{ route('profile.update') }}" class="admin-form" data-unsaved-guard>
                @csrf
                @method('PATCH')

                <div class="field-group">
                    <label for="username">Username</label>
                    <input id="username" name="username" type="text" value="{{ old('username', $user->username) }}" required>
                    @error('username')
                        <p class="field-error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="field-group">
                    <label for="email">Email address</label>
                    <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" required>
                    @error('email')
                        <p class="field-error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Save changes</button>
                </div>
            </form>
        </article>
    </div>

    <section class="admin-panel">
        <div class="panel-header">
            <h2>Change password</h2>
        </div>
        <p class="muted">Confirm your current password before choosing a new one.</p>

        <form method="POST" action="{{ route('profile.password') }}" class="admin-form" data-unsaved-guard>
            @csrf
            @method('PUT')

            <div class="field-group">
                <label for="current_password">Current password</label>
                <input id="current_password" name="current_password" type="password" required autocomplete="current-password">
                @error('current_password')
                    <p class="field-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="form-grid">
                <div class="field-group">
                    <label for="new_password">New password</label>
                    <div class="password-field">
                        <input id="new_password" name="password" type="password" required autocomplete="new-password">
                        <button type="button" class="password-toggle" aria-label="Show password">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>
                        </button>
                    </div>
                    <p class="field-help">At least 6 characters.</p>
                    @error('password')
                        <p class="field-error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="field-group">
                    <label for="password_confirmation">Confirm new password</label>
                    <div class="password-field">
                        <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password">
                        <button type="button" class="password-toggle" aria-label="Show password">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>
                        </button>
                    </div>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Update password</button>
            </div>
        </form>
    </section>
@endsection

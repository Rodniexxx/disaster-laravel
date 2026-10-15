@extends('layouts.auth')

@section('title', 'Login')
@section('card-eyebrow', 'Welcome back')
@section('card-title', 'Login')

@section('visual-kicker', 'Preparedness Game')
@section('visual-title', 'Continue your emergency actions')
@section('visual-text', 'Review safe action plans, revisit response drills, and keep your disaster readiness on track.')


@section('content')
    <form method="post" action="{{ route('login') }}" class="auth-form" data-submit-state>
        @csrf

        <div class="field-group">
            <label for="login_identifier">Username or Email</label>
            <input type="text" id="login_identifier" name="login_identifier"
                   value="{{ old('login_identifier') }}" autocomplete="username" required autofocus>
        </div>

        <div class="field-group">
            <label for="password">Password</label>
            <div class="password-field">
                <input type="password" id="password" name="password" autocomplete="current-password" required>
                <button type="button" class="password-toggle" aria-label="Show password">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>
                </button>
            </div>
        </div>

        <button type="submit" class="btn btn-primary full-width" data-loading-text="Signing in...">
            <span>Login</span>
        </button>
    </form>

    <div class="auth-links">
        <a href="{{ route('register') }}">Create account</a>
    </div>
@endsection

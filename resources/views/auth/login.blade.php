@extends('layouts.auth')

@section('title', 'Login')
@section('card-eyebrow', 'Welcome back')
@section('card-title', 'Login')

@section('visual-kicker', 'Enjoy The Game')
@section('visual-title', 'Keep your progress on track.')
@section('visual-text', 'Review safe action plans, revisit response drills, and keep your disaster preparedness on track.')


@section('content')
    <form method="post" action="{{ route('login') }}" class="auth-form" data-submit-state>
        @csrf

        <div class="field-group">
            <label for="login_identifier">Email</label>
            <input type="text" id="login_identifier" name="login_identifier"
                   value="{{ old('login_identifier') }}" autocomplete="username" required autofocus>
        </div>

        <div class="field-group">
            <label for="password">Password</label>
            <div class="password-field">
                <input type="password" id="password" name="password" autocomplete="current-password" required>
                <button type="button" class="password-toggle" aria-label="Show password">
                    <svg viewBox="0 0 24 24" aria-hidden="true" data-eye><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>
                    <svg viewBox="0 0 24 24" aria-hidden="true" data-eye-off><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                </button>
            </div>
        </div>

        <button type="submit" class="btn btn-primary full-width" data-loading-text="Logging in...">
            <span>Login</span>
        </button>
    </form>

    <div class="auth-links">
        <span>New to Mangno?</span>
        <a href="{{ route('register') }}">Create account</a>
    </div>
@endsection

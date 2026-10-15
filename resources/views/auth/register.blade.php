@extends('layouts.auth')

@section('title', 'Register')
@section('card-eyebrow', 'Create account')
@section('card-title', 'Register')

@section('visual-kicker', 'Create an account')
@section('visual-title', 'Build your emergency readiness')
@section('visual-text', 'Create an account to play the game, enjoy and learn scenarios, and track progress.')


@section('content')
    <form method="post" action="{{ route('register') }}" class="auth-form" data-submit-state>
        @csrf

        <div class="field-group">
            <label for="username">Username</label>
            <input type="text" id="username" name="username" value="{{ old('username') }}"
                   autocomplete="username" required autofocus>
        </div>

        <div class="field-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="{{ old('email') }}"
                   autocomplete="email" required>
        </div>

        <div class="field-group">
            <label for="password">Password</label>
            <div class="password-field">
                <input type="password" id="password" name="password" autocomplete="new-password" required>
                <button type="button" class="password-toggle" aria-label="Show password">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>
                </button>
            </div>
        </div>

        <div class="field-group">
            <label for="password_confirmation">Confirm Password</label>
            <div class="password-field">
                <input type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password" required>
                <button type="button" class="password-toggle" aria-label="Show password">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>
                </button>
            </div>
        </div>

        <button type="submit" class="btn btn-primary full-width" data-loading-text="Creating account...">
            <span>Register</span>
        </button>
    </form>

    <div class="auth-links">
        <span>Already have an account?</span>
        <a href="{{ route('login') }}">Login here</a>
    </div>
@endsection

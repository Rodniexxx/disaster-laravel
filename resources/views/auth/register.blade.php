@extends('layouts.auth')

@section('title', 'Register')
@section('card-eyebrow', 'Create account')
@section('card-title', 'Register')

@section('visual-kicker', 'Create an account')
@section('visual-title', 'Strengthen Your Knowledge of Disaster Preparedness.')
@section('visual-text', 'Create an account to play the game, enjoy and learn scenarios, and track progress.')


@section('content')
    <form method="post" action="{{ route('register') }}" enctype="multipart/form-data" class="auth-form" data-submit-state>
        @csrf
        @include('partials.avatar-upload')

        <div class="field-group">
            <label for="username">Full Name</label>
            <input type="text" id="username" name="username" value="{{ old('username') }}"
                   autocomplete="username" autocapitalize="words" data-registration-username required autofocus>
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
                    <svg viewBox="0 0 24 24" aria-hidden="true" data-eye><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>
                    <svg viewBox="0 0 24 24" aria-hidden="true" data-eye-off><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                </button>
            </div>
        </div>

        <div class="field-group">
            <label for="password_confirmation">Confirm Password</label>
            <div class="password-field">
                <input type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password" required>
                <button type="button" class="password-toggle" aria-label="Show password">
                    <svg viewBox="0 0 24 24" aria-hidden="true" data-eye><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>
                    <svg viewBox="0 0 24 24" aria-hidden="true" data-eye-off><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
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

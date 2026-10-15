@extends('layouts.admin')

@section('title', 'Settings')
@section('heading', 'Settings')
@section('eyebrow', 'System')

@section('back')
    <x-back-button />
@endsection

@section('content')
    <section class="admin-panel">
        <div class="panel-header">
            <h2>Website Settings</h2>
        </div>
        <form method="post" action="{{ route('admin.settings.update') }}" class="admin-form" data-unsaved-guard>
            @csrf
            @method('PUT')

            <div class="form-grid">
                <div class="field-group">
                    <label for="site_name">Site Name</label>
                    <input type="text" id="site_name" name="site_name" value="{{ old('site_name', $settings['site_name'] ?? 'MANGNO') }}" required>
                </div>
                <div class="field-group">
                    <label for="site_tagline">Tagline</label>
                    <input type="text" id="site_tagline" name="site_tagline" value="{{ old('site_tagline', $settings['site_tagline'] ?? 'Ready Before It Happens.') }}" required>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Save Settings</button>
            </div>
        </form>
    </section>

    <section class="admin-panel">
        <div class="panel-header">
            <h2>Environment</h2>
        </div>
        <div class="info-stack">
            <div class="kpi-row">
                <span>Application</span>
                <strong>{{ config('app.name') }}</strong>
            </div>
            <div class="kpi-row">
                <span>Laravel version</span>
                <strong>{{ app()->version() }}</strong>
            </div>
            <div class="kpi-row">
                <span>Environment</span>
                <strong>{{ config('app.env') }}</strong>
            </div>
            <div class="kpi-row">
                <span>Debug mode</span>
                <strong>{{ config('app.debug') ? 'Enabled' : 'Disabled' }}</strong>
            </div>
        </div>
    </section>
@endsection

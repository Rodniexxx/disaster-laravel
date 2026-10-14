<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AuditLogger;
use App\Services\SettingsService;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function __construct(
        private readonly SettingsService $settings,
        private readonly AuditLogger $audit,
    ) {}

    public function edit()
    {
        return view('admin.settings.edit', [
            'settings' => $this->settings->all(),
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'site_name' => ['required', 'string', 'max:120'],
            'site_tagline' => ['required', 'string', 'max:120'],
        ]);

        $this->settings->put($data);

        $this->audit->log($request->user(), 'updated settings', 'settings', ['site_name' => $data['site_name']]);

        return back()->with('status', 'Settings saved.');
    }
}

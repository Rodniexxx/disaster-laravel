<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Services\SettingsService;
use App\Support\DisasterCatalog;

class HomeController extends Controller
{
    public function __invoke(DisasterCatalog $catalog, SettingsService $settings)
    {
        return view('landing', [
            'scenarios' => $catalog->all(),
            'announcements' => Announcement::query()->published()->orderByDesc('publish_date')->limit(3)->get(),
            'settings' => $settings->all(),
        ]);
    }
}

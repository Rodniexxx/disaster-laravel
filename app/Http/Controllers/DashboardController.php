<?php

namespace App\Http\Controllers;

use App\Services\PlayerStatsService;
use App\Services\CompletionCertificateService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request, PlayerStatsService $stats, CompletionCertificateService $certificates)
    {
        return view('player.dashboard', [
            'stats' => $stats->forUser($request->user()),
            'certificate' => $certificates->status($request->user()),
        ]);
    }
}

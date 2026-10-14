<?php

namespace App\Http\Controllers;

use App\Services\PlayerStatsService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request, PlayerStatsService $stats)
    {
        return view('player.dashboard', [
            'stats' => $stats->forUser($request->user()),
        ]);
    }
}

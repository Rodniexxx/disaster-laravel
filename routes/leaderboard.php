<?php

use App\Http\Controllers\Api\PublicLeaderboardController;
use Illuminate\Support\Facades\Route;

Route::middleware(['throttle:api', 'auth:sanctum', 'active'])->group(function () {
    Route::get('readiness/leaderboard/view', [PublicLeaderboardController::class, 'index']);
    Route::get('public-profile', [PublicLeaderboardController::class, 'show']);
    Route::get('public-profile/avatar', [PublicLeaderboardController::class, 'avatar']);
});

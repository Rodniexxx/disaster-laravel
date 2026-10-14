<?php

use App\Http\Controllers\Api\AnnouncementController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\LeaderboardController;
use App\Http\Controllers\Api\ScenarioController;
use App\Http\Controllers\Api\ScoreController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| MANGNO Unity integration API
|--------------------------------------------------------------------------
|
| Paths and payloads mirror the legacy PHP endpoints exactly so the existing
| Unity client keeps working without changes. Authentication is cookie/session
| based (see bootstrap/app.php) rather than token based.
|
*/

Route::post('login', [AuthController::class, 'login'])
    ->middleware('throttle:api-login')
    ->name('api.login');

Route::post('register', [AuthController::class, 'register'])
    ->middleware('throttle:api-login')
    ->name('api.register');

Route::post('save_score', [ScoreController::class, 'save'])
    ->middleware('throttle:api')
    ->name('api.save-score');

Route::get('get_leaderboard', [LeaderboardController::class, 'get'])
    ->middleware('throttle:api')
    ->name('api.leaderboard');

Route::get('scenarios', [ScenarioController::class, 'index'])
    ->middleware('throttle:api')
    ->name('api.scenarios');

Route::get('announcements', [AnnouncementController::class, 'index'])
    ->middleware('throttle:api')
    ->name('api.announcements');

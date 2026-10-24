<?php

use App\Http\Controllers\Api\AnnouncementController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\LeaderboardController;
use App\Http\Controllers\Api\ScenarioController;
use App\Http\Controllers\Api\ScoreController;
use Illuminate\Support\Facades\Route;

Route::middleware('throttle:api-login')->group(function () {
    Route::post('login', [AuthController::class, 'login'])->name('api.login');
    Route::post('register', [AuthController::class, 'register'])->name('api.register');
});

Route::middleware('throttle:api')->group(function () {
    Route::get('readiness/leaderboard', [\App\Http\Controllers\Api\ReadinessController::class, 'leaderboard']);
    Route::get('get_leaderboard', [LeaderboardController::class, 'get'])->name('api.leaderboard');
    Route::get('scenarios', [ScenarioController::class, 'index'])->name('api.scenarios');
    Route::get('announcements', [AnnouncementController::class, 'index'])->name('api.announcements');

    Route::middleware(['auth:sanctum', 'active'])->group(function () {
        Route::get('readiness', [\App\Http\Controllers\Api\ReadinessController::class, 'show']);
        Route::get('readiness/certificate', [\App\Http\Controllers\Api\CompletionCertificateController::class, 'show']);
        Route::post('readiness/certificate/issue', [\App\Http\Controllers\Api\CompletionCertificateController::class, 'issue']);
        Route::get('readiness/certificate/pdf', [\App\Http\Controllers\Api\CompletionCertificateController::class, 'pdf']);
        Route::post('readiness/attempts', [\App\Http\Controllers\Api\ReadinessController::class, 'store']);
        Route::post('save_score', [ScoreController::class, 'save'])->name('api.save-score');
        Route::get('me', [AuthController::class, 'me'])->name('api.me');
        Route::post('logout', [AuthController::class, 'logout'])->name('api.logout');
    });
});

require __DIR__.'/profile.php';

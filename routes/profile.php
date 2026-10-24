<?php

use App\Http\Controllers\Api\PlayerProfileController;
use Illuminate\Support\Facades\Route;

Route::middleware(['throttle:api', 'auth:sanctum', 'active'])->group(function () {
    Route::get('profile', [PlayerProfileController::class, 'show']);
    Route::post('profile', [PlayerProfileController::class, 'update']);
    Route::get('profile/avatar', [PlayerProfileController::class, 'avatar']);
});

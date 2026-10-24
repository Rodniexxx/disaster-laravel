<?php

use App\Http\Controllers\Admin\AnnouncementController as AdminAnnouncementController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\LeaderboardController as AdminLeaderboardController;
use App\Http\Controllers\Admin\LogController as AdminLogController;
use App\Http\Controllers\Admin\ProgressController as AdminProgressController;
use App\Http\Controllers\Admin\QuestionController as AdminQuestionController;
use App\Http\Controllers\Admin\SettingsController as AdminSettingsController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\VersionController as AdminVersionController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DownloadController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LeaderboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PlayerCertificateController;
use App\Http\Controllers\ScoreController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

Route::get('/download', DownloadController::class)->name('download');
Route::get('/download/windows', [DownloadController::class, 'windows'])->name('downloads.windows');
Route::get('/download/android', [DownloadController::class, 'android'])->name('downloads.android');
Route::get('/download/{platform}/file', [DownloadController::class, 'file'])
    ->where('platform', 'windows|android')
    ->name('download.file');

Route::get('/leaderboard', [LeaderboardController::class, 'index'])->name('leaderboard.index');

/* -------------------------------------------------------------------------
 | Guest authentication routes
 | ---------------------------------------------------------------------- */
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store']);

    Route::get('/register', [RegisterController::class, 'create'])->name('register');
    Route::post('/register', [RegisterController::class, 'store']);
});

Route::post('/logout', [LoginController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

/* -------------------------------------------------------------------------
 | Player area (authenticated and active accounts)
 | ---------------------------------------------------------------------- */
Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/dashboard/certificate', [PlayerCertificateController::class, 'show'])->name('dashboard.certificate');
    Route::post('/dashboard/certificate/issue', [PlayerCertificateController::class, 'issue'])->name('dashboard.certificate.issue');
    Route::get('/dashboard/certificate.pdf', [PlayerCertificateController::class, 'pdf'])->name('dashboard.certificate.pdf');

    Route::get('/scores', [ScoreController::class, 'index'])->name('scores.index');
    Route::get('/scores/categories', [ScoreController::class, 'categories'])->name('scores.categories');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');
});

/* -------------------------------------------------------------------------
 | Administrator area (authenticated, active and admin role)
 | ---------------------------------------------------------------------- */
Route::middleware(['auth', 'active', 'admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/', AdminDashboardController::class)->name('dashboard');

        Route::patch('users/{user}/status', [AdminUserController::class, 'toggleStatus'])->name('users.toggle-status');
        Route::resource('users', AdminUserController::class)->except(['create', 'show', 'edit']);

        Route::patch('questions/{question}/toggle', [AdminQuestionController::class, 'toggle'])->name('questions.toggle');
        Route::resource('questions', AdminQuestionController::class)->except(['create', 'show', 'edit']);

        Route::get('progress', [AdminProgressController::class, 'index'])->name('progress.index');

        Route::resource('announcements', AdminAnnouncementController::class)->except(['create', 'show', 'edit']);

        Route::put('versions/content', [AdminVersionController::class, 'updateContent'])->name('versions.content');
        Route::get('versions/{version}/file', [AdminVersionController::class, 'download'])->name('versions.download');
        Route::patch('versions/{version}/toggle', [AdminVersionController::class, 'toggle'])->name('versions.toggle');
        Route::patch('versions/{version}/latest', [AdminVersionController::class, 'setLatest'])->name('versions.latest');
        Route::resource('versions', AdminVersionController::class)->except(['create', 'show', 'edit']);

        Route::get('logs', [AdminLogController::class, 'index'])->name('logs.index');

        Route::get('leaderboard', [AdminLeaderboardController::class, 'index'])->name('leaderboard');
        Route::delete('leaderboard/scores', [AdminLeaderboardController::class, 'destroy'])->name('leaderboard.destroy');

        Route::get('settings', [AdminSettingsController::class, 'edit'])->name('settings.edit');
        Route::put('settings', [AdminSettingsController::class, 'update'])->name('settings.update');
    });

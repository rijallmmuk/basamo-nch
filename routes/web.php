<?php

use App\Http\Controllers\Portal\AuthController;
use App\Http\Controllers\Portal\DiscussionController;
use App\Http\Controllers\Portal\HomeController;
use App\Http\Controllers\Portal\LeaderboardController;
use App\Http\Controllers\Portal\ModuleController;
use App\Http\Controllers\Portal\NotificationController;
use App\Http\Controllers\Portal\PageController;
use App\Http\Controllers\Portal\QuizController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('portal.login'));

Route::prefix('portal')->name('portal.')->group(function () {
    // Guest only
    Route::middleware('guest')->group(function () {
        Route::get('login', [AuthController::class, 'showLogin'])->name('login');
        Route::post('login', [AuthController::class, 'login']);
        Route::get('register', [AuthController::class, 'showRegister'])->name('register');
        Route::post('register', [AuthController::class, 'register']);
    });

    Route::post('logout', [AuthController::class, 'logout'])->name('logout');

    // Protected (warga & umkm_owner only)
    Route::middleware('portal')->group(function () {
        Route::get('/', [HomeController::class, 'index'])->name('home');

        Route::get('notifications', [NotificationController::class, 'index'])->name('notifications');
        Route::get('leaderboard', [LeaderboardController::class, 'index'])->name('leaderboard');

        Route::prefix('modules')->name('modules.')->group(function () {
            Route::get('/', [ModuleController::class, 'index'])->name('index');
            Route::get('{module:slug}', [ModuleController::class, 'show'])->name('show');
            Route::get('{module:slug}/pages/{page}', [PageController::class, 'show'])->name('pages.show');
            Route::get('{module:slug}/quiz', [QuizController::class, 'show'])->name('quiz');

            // Forum diskusi per modul
            Route::get('{module:slug}/discuss', [DiscussionController::class, 'index'])->name('discuss');
            Route::post('{module:slug}/discuss', [DiscussionController::class, 'store'])->name('discuss.store');
            Route::get('{module:slug}/discuss/{discussion}', [DiscussionController::class, 'show'])->name('discuss.show');
            Route::post('{module:slug}/discuss/{discussion}/reply', [DiscussionController::class, 'reply'])->name('discuss.reply');
        });
    });
});

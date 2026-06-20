<?php

use App\Http\Controllers\Portal\AuthController;
use App\Http\Controllers\Portal\DiscussionController;
use App\Http\Controllers\Portal\HomeController;
use App\Http\Controllers\Portal\LeaderboardController;
use App\Http\Controllers\Portal\ModuleController;
use App\Http\Controllers\Portal\NotificationController;
use App\Http\Controllers\Portal\PageController;
use App\Http\Controllers\Portal\PasswordController;
use App\Http\Controllers\Portal\QuizController;
use App\Http\Controllers\Portal\UmkmController;
use App\Http\Controllers\Portal\UmkmProductController;
use App\Http\Controllers\Public\UmkmCatalogController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('portal.login'));

// ── LAPISAN 1: Frontend publik (tanpa login) ──────────────────────────
Route::prefix('umkm')->name('public.umkm.')->group(function () {
    Route::get('/', [UmkmCatalogController::class, 'index'])->name('index');
    Route::get('{product:slug}', [UmkmCatalogController::class, 'show'])->name('show');
});

Route::prefix('portal')->name('portal.')->group(function () {
    // Guest only — akun warga dibuat Admin Nagari (tanpa self-register).
    Route::middleware('guest')->group(function () {
        Route::get('login', [AuthController::class, 'showLogin'])->name('login');
        Route::post('login', [AuthController::class, 'login']);
    });

    Route::post('logout', [AuthController::class, 'logout'])->name('logout');

    // Protected (akun portal warga saja)
    Route::middleware('portal')->group(function () {
        // Ganti sandi (juga jadi gerbang paksa-ganti saat login pertama via OTP)
        Route::get('ganti-sandi', [PasswordController::class, 'edit'])->name('password.edit');
        Route::post('ganti-sandi', [PasswordController::class, 'update'])->name('password.update');

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

        // Lapak UMKM "Produk Saya" — khusus warga dengan akses UMKM.
        Route::middleware('umkm.owner')->prefix('umkm')->name('umkm.')->group(function () {
            Route::get('/', [UmkmController::class, 'index'])->name('index');
            Route::get('profil', [UmkmController::class, 'editProfile'])->name('profile.edit');
            Route::post('profil', [UmkmController::class, 'storeProfile'])->name('profile.store');

            Route::get('produk/baru', [UmkmProductController::class, 'create'])->name('products.create');
            Route::post('produk', [UmkmProductController::class, 'store'])->name('products.store');
            Route::get('produk/{product}/ubah', [UmkmProductController::class, 'edit'])->name('products.edit');
            Route::put('produk/{product}', [UmkmProductController::class, 'update'])->name('products.update');
            Route::delete('produk/{product}', [UmkmProductController::class, 'destroy'])->name('products.destroy');
        });
    });
});

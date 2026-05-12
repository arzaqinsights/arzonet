<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TrackingController;
use App\Http\Controllers\SESWebhookController;
use App\Http\Controllers\AuthController;
/*
|--------------------------------------------------------------------------
| Public Routes (main domain: email.test / domain.com)
|--------------------------------------------------------------------------
*/

// 1. Main Domain Routes (Landing Pages)
Route::domain(config('app.domain'))->group(function () {
    Route::get('/', fn() => view('landing.index'))->name('home');
    Route::get('/contact', fn() => view('landing.contact'))->name('contact');
    Route::get('/privacy-policy', fn() => view('landing.privacy'))->name('privacy');
    Route::get('/terms', fn() => view('landing.terms'))->name('terms');
    Route::get('/refund-policy', fn() => view('landing.refund'))->name('refund');
});

// 2. Public Tracking & Webhooks (Global)
Route::get('/t/o/{token}', [TrackingController::class, 'open'])->name('track.open');
Route::get('/t/c/{token}', [TrackingController::class, 'click'])->name('track.click');
Route::get('/unsubscribe/{token}', [TrackingController::class, 'unsubscribe'])->name('unsubscribe');
Route::post('/webhooks/ses', [SESWebhookController::class, 'handle'])->name('webhooks.ses');
Route::post('/webhooks/cashfree', [\App\Http\Controllers\WebhookController::class, 'handleCashfree'])->name('webhooks.cashfree');

// 3. Auth Routes (Account Subdomain)
Route::domain('account.' . config('app.domain'))->group(function () {
    Route::middleware('guest')->group(function () {
        Route::get('/', [AuthController::class, 'showLogin'])->name('login');
        Route::post('/auth/start', [AuthController::class, 'start'])->name('auth.start');
        Route::post('/login', [AuthController::class, 'login'])->name('submit.login');
        Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
        Route::post('/register', [AuthController::class, 'register'])->name('submit.register');
    });
    
    Route::match(['GET', 'POST'], '/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');
});

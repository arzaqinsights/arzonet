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

// Landing Pages
Route::get('/', fn() => view('landing.index'))->name('home');
Route::get('/contact', fn() => view('landing.contact'))->name('contact');
Route::get('/privacy-policy', fn() => view('landing.privacy'))->name('privacy');
Route::get('/terms', fn() => view('landing.terms'))->name('terms');
Route::get('/refund-policy', fn() => view('landing.refund'))->name('refund');


// Public Tracking (must stay on root domain so pixel/link URLs resolve)
Route::get('/t/o/{token}', [TrackingController::class, 'open'])->name('track.open');
Route::get('/t/c/{token}', [TrackingController::class, 'click'])->name('track.click');
Route::get('/unsubscribe/{token}', [TrackingController::class, 'unsubscribe'])->name('unsubscribe');

// SES Webhook (public — no CSRF)
Route::post('/webhooks/ses', [SESWebhookController::class, 'handle'])->name('webhooks.ses');
Route::post('/webhooks/cashfree', [\App\Http\Controllers\WebhookController::class, 'handleCashfree'])->name('webhooks.cashfree');

// Auth Routes
Route::domain('account.' . config('app.domain'))->group(function () {
    Route::middleware('guest')->group(function () {
        Route::post('/auth/start', [AuthController::class, 'start'])->name('auth.start');
        Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
        Route::post('/login', [AuthController::class, 'login']);
        Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
        Route::post('/register', [AuthController::class, 'register']);
    });
    
    Route::match(['GET', 'POST'], '/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');
});

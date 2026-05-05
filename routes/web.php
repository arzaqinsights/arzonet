<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TrackingController;
use App\Http\Controllers\SESWebhookController;

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
Route::get('/t/o/{token}',   [TrackingController::class, 'open'])->name('track.open');
Route::get('/t/c/{token}',   [TrackingController::class, 'click'])->name('track.click');
Route::get('/unsubscribe/{token}', [TrackingController::class, 'unsubscribe'])->name('unsubscribe');

// SES Webhook (public — no CSRF)
Route::post('/webhooks/ses', [SESWebhookController::class, 'handle'])->name('webhooks.ses');

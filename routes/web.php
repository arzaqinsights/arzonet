<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TrackingController;
use App\Http\Controllers\SESWebhookController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UnsubscribeController;
/*
|--------------------------------------------------------------------------
| Public Routes (main domain: email.test / domain.com)
|--------------------------------------------------------------------------
*/

// 1. Main Domain Routes (Landing Pages)
Route::domain(config('app.domain'))->group(function () {
    Route::get('/', fn() => view('landing.index'))->name('home');
    Route::get('/pricing', [\App\Http\Controllers\PlansController::class, 'pricingPage'])->name('pricing');
    Route::get('/contact', fn() => view('landing.contact'))->name('contact');
    Route::get('/privacy-policy', fn() => view('landing.privacy'))->name('privacy');
    Route::get('/terms', fn() => view('landing.terms'))->name('terms');
    Route::get('/refund-policy', fn() => view('landing.refund'))->name('refund');
});


// 2. Public Tracking & Webhooks (Global)
Route::get('/t/o/{token}', [TrackingController::class, 'open'])->name('track.open');
Route::get('/t/c/{token}', [TrackingController::class, 'click'])->name('track.click');
Route::match(['GET', 'POST'], '/unsubscribe/{token}', [TrackingController::class, 'unsubscribe'])->name('unsubscribe');
Route::get('/unsubscribe/confirm/{id}', [UnsubscribeController::class, 'show'])->name('unsubscribe.show');
Route::post('/unsubscribe/confirm/{id}', [UnsubscribeController::class, 'confirm'])->name('unsubscribe.confirm');
Route::post('/webhooks/ses', [SESWebhookController::class, 'handle'])->name('webhooks.ses');
// Route::post('/webhooks/cashfree', [\App\Http\Controllers\WebhookController::class, 'handleCashfree'])->name('webhooks.cashfree');

// Public Signup Forms & Opt-In Confirmations
Route::get('/forms/{token}', [\App\Http\Controllers\PublicFormController::class, 'show'])->name('public.forms.show');
Route::get('/forms/{token}/widget.js', [\App\Http\Controllers\PublicFormController::class, 'widgetJs'])->name('public.forms.widget-js');
Route::post('/forms/{token}', [\App\Http\Controllers\PublicFormController::class, 'submit'])->name('public.forms.submit');
Route::post('/forms/{token}/progress', [\App\Http\Controllers\PublicFormController::class, 'recordProgress'])->name('public.forms.progress');
Route::get('/confirm-subscription/{token}', [\App\Http\Controllers\PublicFormController::class, 'confirm'])
    ->name('public.confirm-subscription')
    ->where('token', '.*');

// 3. Auth Routes (Account Subdomain)
Route::domain('account.' . config('app.domain'))->group(function () {
    Route::middleware('guest')->group(function () {
        Route::get('/', [AuthController::class, 'showLogin'])->name('login');
        Route::post('/', [AuthController::class, 'login']);
        Route::post('/auth/start', [AuthController::class, 'start'])->name('auth.start');
        Route::post('/login', [AuthController::class, 'login'])->name('submit.login');
        Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
        Route::post('/register', [AuthController::class, 'register'])->name('submit.register');

        // Password Reset Routes
        Route::get('/forgot-password', [AuthController::class, 'showForgotPassword'])->name('password.request');
        Route::post('/forgot-password', [AuthController::class, 'sendResetLink'])->name('password.email');
        Route::get('/reset-password/{token}', [AuthController::class, 'showResetPassword'])->name('password.reset');
        Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.update');
    });
    
    Route::middleware('auth')->group(function () {
        Route::match(['GET', 'POST'], '/logout', [AuthController::class, 'logout'])->name('logout');

        // Email Verification Routes
        Route::get('/email/verify', [AuthController::class, 'showVerifyEmail'])->name('verification.notice');
        Route::get('/email/verify/{id}/{hash}', [AuthController::class, 'verifyEmail'])->middleware(['signed'])->name('verification.verify');
        Route::post('/email/verification-notification', [AuthController::class, 'resendVerificationEmail'])->middleware(['throttle:6,1'])->name('verification.send');
    });
});

Route::get('/diagnose-production-queue', function (\Illuminate\Http\Request $request) {
    if ($request->query('token') !== 'secret-diagnostics-9932') {
        abort(403);
    }
    $redisLen = 0;
    $redisError = null;
    try {
        $redisLen = \Illuminate\Support\Facades\Redis::llen('webhook:sendgrid:buffer');
    } catch (\Exception $e) {
        $redisError = $e->getMessage();
    }
    
    $failedCount = 0;
    $failedJobs = [];
    $failedError = null;
    try {
        $failedCount = \Illuminate\Support\Facades\DB::table('failed_jobs')->count();
        $failedJobs = \Illuminate\Support\Facades\DB::table('failed_jobs')
            ->orderBy('failed_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function($job) {
                return [
                    'id' => $job->id,
                    'queue' => $job->queue,
                    'failed_at' => $job->failed_at,
                    'payload_name' => json_decode($job->payload)->displayName ?? 'Unknown',
                    'exception' => substr($job->exception, 0, 300)
                ];
            });
    } catch (\Exception $e) {
        $failedError = $e->getMessage();
    }
    
    $todayLogs = [];
    $logsError = null;
    try {
        $todayLogs = \Illuminate\Support\Facades\DB::table('email_logs')
            ->whereDate('created_at', date('Y-m-d'))
            ->select('status', \Illuminate\Support\Facades\DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get();
    } catch (\Exception $e) {
        $logsError = $e->getMessage();
    }
    
    return response()->json([
        'queue_connection' => config('queue.default'),
        'redis_buffer_len' => $redisLen,
        'redis_error' => $redisError,
        'failed_count' => $failedCount,
        'failed_jobs' => $failedJobs,
        'failed_error' => $failedError,
        'today_logs' => $todayLogs,
        'logs_error' => $logsError,
    ]);
});


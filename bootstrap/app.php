<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            $urlHost = parse_url(config('app.url'), PHP_URL_HOST);
            $baseDomain = config('app.domain') ?: (str_starts_with($urlHost ?? '', 'admin.') ? substr($urlHost, 6) : $urlHost);
            $adminDomain = 'admin.' . $baseDomain;
            
            // 1. Public Tracking & Signup Forms Routes on Admin Subdomain (No Auth)
            Route::middleware(['web'])
                ->domain($adminDomain)
                ->group(function() {
                    Route::get('/t/o/{token}',   [\App\Http\Controllers\TrackingController::class, 'open'])->name('admin.track.open');
                    Route::get('/t/c/{token}',   [\App\Http\Controllers\TrackingController::class, 'click'])->name('admin.track.click');
                    Route::match(['GET', 'POST'], '/unsubscribe/{token}', [\App\Http\Controllers\TrackingController::class, 'unsubscribe'])->name('admin.unsubscribe');
                });
                
            // 2. Authenticated Admin Routes
            Route::middleware(['web', 'auth'])
                ->domain($adminDomain)
                ->group(base_path('routes/admin.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');
        $middleware->redirectGuestsTo(fn () => route('login'));
        $middleware->redirectUsersTo(fn() => auth()->user()?->isSuperAdmin() ? route('admin.super.dashboard') : route('admin.dashboard'));
        $middleware->validateCsrfTokens(except: [
            'webhooks/*',
            'api/sns/*',
            'api/whatsapp/webhook',
            '*/webhooks/cashfree',
            '*/webhooks/sendgrid',
            'unsubscribe/*',
            '*/unsubscribe/*',
        ]);
        $middleware->web(append: [
            \App\Http\Middleware\SwapTeamUser::class,
        ]);
        $middleware->alias([
            'super_admin' => \App\Http\Middleware\SuperAdminMiddleware::class,
            'whatsapp.verify' => \App\Http\Middleware\VerifyWhatsAppSignature::class,
            'permission' => \App\Http\Middleware\CheckPermission::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();

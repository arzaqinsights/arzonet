<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmailListController;
use App\Http\Controllers\TemplateController;
use App\Http\Controllers\CampaignController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\BlacklistController;
use App\Http\Controllers\SenderController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\DomainController;

/*
|--------------------------------------------------------------------------
| Admin Routes — accessed via admin.email.test (local) / admin.domain.com (prod)
|--------------------------------------------------------------------------
*/

Route::name('admin.')->group(function () {

    Route::get('/horizon', function () {
        return redirect('/horizon/dashboard');
    });
    // Dashboard
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Email Lists
    Route::prefix('email-lists')->name('email-lists.')->group(function () {
        Route::get('/', [EmailListController::class, 'index'])->name('index');
        Route::get('/create', [EmailListController::class, 'create'])->name('create');
        Route::post('/', [EmailListController::class, 'store'])->name('store');
        Route::get('/{emailList}', [EmailListController::class, 'show'])->name('show');
        Route::post('/{id}/mapping', [EmailListController::class, 'storeMapping'])->name('store-mapping');
        Route::get('/{emailList}/status', [EmailListController::class, 'checkStatus'])->name('status');
        Route::post('/{emailList}/filter', [EmailListController::class, 'filterEmails'])->name('filter');
        Route::get('/{emailList}/emails/{emailId}', [EmailListController::class, 'getEmail'])->name('get-email');
        Route::put('/{emailList}/emails/{emailId}', [EmailListController::class, 'updateEmail'])->name('update-email');
        Route::delete('/{emailList}/emails/{emailId}', [EmailListController::class, 'destroyEmail'])->name('destroy-email');
        Route::post('/{emailList}/add-contact', [EmailListController::class, 'addContact'])->name('add-contact');
        Route::post('/{emailList}/add-alternate-channel', [EmailListController::class, 'addAlternateChannel'])->name('add-alternate-channel');
        Route::post('/{emailList}/add-custom-column', [EmailListController::class, 'addCustomColumn'])->name('add-custom-column');
        Route::post('/{emailList}/import-more', [EmailListController::class, 'importMore'])->name('import-more');
        Route::post('/{emailList}/undo-import/{logId}', [EmailListController::class, 'undoImport'])->name('undo-import');
        Route::post('/{emailList}/scrub', [EmailListController::class, 'scrubList'])->name('scrub');
        Route::post('/{emailList}/bulk-action', [EmailListController::class, 'bulkAction'])->name('bulk-action');
        Route::get('/{emailList}/export', [EmailListController::class, 'exportContacts'])->name('export');
        Route::get('/{emailList}/fix-invalid', [EmailListController::class, 'fixInvalid'])->name('fix-invalid');
        Route::delete('/{emailList}/fix-invalid/delete-all', [EmailListController::class, 'deleteAllInvalid'])->name('fix-invalid.delete-all');
        Route::post('/{emailList}/save-invalid', [EmailListController::class, 'saveInvalid'])->name('save-invalid');
        Route::get('/{emailList}/duplicates', [EmailListController::class, 'resolveDuplicates'])->name('duplicates.index');
        Route::post('/{emailList}/duplicates/resolve', [EmailListController::class, 'resolveDuplicatesAction'])->name('duplicates.resolve');
        Route::patch('/{emailList}/update-name', [EmailListController::class, 'updateName'])->name('update-name');
        Route::get('/check-mx', [EmailListController::class, 'checkMX'])->name('check-mx');
        Route::delete('/{emailList}', [EmailListController::class, 'destroy'])->name('destroy');
    });

    // Templates
    Route::prefix('templates')->name('templates.')->group(function () {
        Route::get('/', [TemplateController::class, 'index'])->name('index');
        Route::get('/create', [TemplateController::class, 'create'])->name('create');
        Route::post('/', [TemplateController::class, 'store'])->name('store');
        Route::get('/{template}/edit', [TemplateController::class, 'edit'])->name('edit');
        Route::put('/{template}', [TemplateController::class, 'update'])->name('update');
        Route::get('/{template}/preview', [TemplateController::class, 'preview'])->name('preview');
        Route::post('/{template}/test', [TemplateController::class, 'sendTest'])->name('send-test');
        Route::post('/{template}/clone', [TemplateController::class, 'clone'])->name('clone');
        Route::delete('/{template}', [TemplateController::class, 'destroy'])->name('destroy');
    });

    // Campaigns
    Route::prefix('campaigns')->name('campaigns.')->group(function () {
        Route::get('/', [CampaignController::class, 'index'])->name('index');
        Route::get('/create', [CampaignController::class, 'create'])->name('create');
        Route::get('/{campaign}/wizard', [CampaignController::class, 'wizard'])->name('wizard');
        Route::post('/{campaign}/save-step', [CampaignController::class, 'saveStep'])->name('save-step');
        Route::post('/', [CampaignController::class, 'store'])->name('store');
        Route::get('/{campaign}', [CampaignController::class, 'show'])->name('show');
        Route::post('/{campaign}/send', [CampaignController::class, 'send'])->name('send');
        Route::post('/{campaign}/pause', [CampaignController::class, 'pause'])->name('pause');
        Route::post('/{campaign}/resume', [CampaignController::class, 'resume'])->name('resume');
        Route::post('/{campaign}/cancel', [CampaignController::class, 'cancel'])->name('cancel');
        Route::post('/{campaign}/retry-failed', [CampaignController::class, 'retryFailed'])->name('retry-failed');
        Route::get('/{campaign}/report', [CampaignController::class, 'report'])->name('report');
        Route::get('/{campaign}/status', [CampaignController::class, 'checkStatus'])->name('status');
        Route::post('/{campaign}/clone', [CampaignController::class, 'clone'])->name('clone');
        Route::post('/{campaign}/send-test', [CampaignController::class, 'sendTest'])->name('send-test');
        Route::get('/{campaign}/export-logs', [CampaignController::class, 'exportLogs'])->name('export-logs');
        Route::get('/preview', [CampaignController::class, 'preview'])->name('preview');
        Route::get('/{campaign}/edit', [CampaignController::class, 'edit'])->name('edit');
        Route::put('/{campaign}', [CampaignController::class, 'update'])->name('update');
        Route::delete('/{campaign}', [CampaignController::class, 'destroy'])->name('destroy');
    });

    // Senders
    Route::prefix('senders')->name('senders.')->group(function () {
        Route::get('/', [SenderController::class, 'index'])->name('index');
        Route::post('/', [SenderController::class, 'store'])->name('store');
        Route::get('/{sender}/edit', [SenderController::class, 'edit'])->name('edit');
        Route::put('/{sender}', [SenderController::class, 'update'])->name('update');
        Route::get('/{sender}/verify', [SenderController::class, 'verify'])->name('verify');
        Route::delete('/{sender}', [SenderController::class, 'destroy'])->name('destroy');
        Route::post('/{sender}/test', [SenderController::class, 'test'])->name('test');
        Route::post('/{sender}/retry', [SenderController::class, 'retry'])->name('retry');
    });

    // Blacklist
    Route::prefix('blacklist')->name('blacklist.')->group(function () {
        Route::get('/', [BlacklistController::class, 'index'])->name('index');
        Route::post('/', [BlacklistController::class, 'store'])->name('store');
        Route::post('/bulk', [BlacklistController::class, 'bulkStore'])->name('bulk-store');
        Route::delete('/{blacklistedEmail}', [BlacklistController::class, 'destroy'])->name('destroy');
    });

    // Domains
    Route::prefix('domains')->name('domains.')->group(function () {
        Route::get('/', [DomainController::class, 'index'])->name('index');
        Route::post('/', [DomainController::class, 'store'])->name('store');
        Route::get('/{domain}', [DomainController::class, 'show'])->name('show');
        Route::post('/{domain}/verify', [DomainController::class, 'verify'])->name('verify');
        Route::delete('/{domain}', [DomainController::class, 'destroy'])->name('destroy');
    });

    // Settings
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');

    // Billing & Plans
    Route::prefix('billing')->name('billing.')->group(function () {
        Route::get('/plans', [\App\Http\Controllers\PlansController::class, 'index'])->name('plans');
        Route::post('/purchase', [\App\Http\Controllers\PlansController::class, 'purchase'])->name('purchase');
        Route::get('/payment-return', [\App\Http\Controllers\PlansController::class, 'paymentReturn'])->name('payment-return');
        Route::get('/invoices', [\App\Http\Controllers\InvoicesController::class, 'index'])->name('invoices.index');
        Route::get('/invoices/{invoice}', [\App\Http\Controllers\InvoicesController::class, 'show'])->name('invoices.show');
    });

    // Cashfree Webhook (inside admin subdomain so server handles it)
    Route::post('/webhooks/cashfree', [\App\Http\Controllers\WebhookController::class, 'handleCashfree'])
        ->withoutMiddleware(['auth'])
        ->name('webhooks.cashfree');

    // SendGrid Event Webhook (opens, clicks, bounces, etc.)
    Route::post('/webhooks/sendgrid', [\App\Http\Controllers\SendGridWebhookController::class, 'handle'])
        ->withoutMiddleware(['auth'])
        ->name('webhooks.sendgrid');

    // Users
    Route::prefix('users')->name('users.')->group(function () {
        Route::get('/', [UserController::class, 'index'])->name('index');
        Route::get('/create', [UserController::class, 'create'])->name('create');
        Route::post('/', [UserController::class, 'store'])->name('store');
        Route::get('/{user}', [UserController::class, 'show'])->name('show');
        Route::get('/{user}/edit', [UserController::class, 'edit'])->name('edit');
        Route::put('/{user}', [UserController::class, 'update'])->name('update');
        Route::delete('/{user}', [UserController::class, 'destroy'])->name('destroy');
    });

    // Media
    Route::post('/media/upload', [MediaController::class, 'upload'])->name('media.upload');
    Route::get('/media', [MediaController::class, 'index'])->name('media.index');

    // WhatsApp
    Route::prefix('whatsapp')->name('whatsapp.')->group(function () {
        // Accounts / Phone Numbers
        Route::prefix('accounts')->name('accounts.')->group(function () {
            Route::get('/', [\App\Http\Controllers\WhatsAppAccountController::class, 'index'])->name('index');
            Route::post('/', [\App\Http\Controllers\WhatsAppAccountController::class, 'store'])->name('store');
            Route::post('/{account}/register', [\App\Http\Controllers\WhatsAppAccountController::class, 'register'])->name('register');
            Route::delete('/{whatsappAccount}', [\App\Http\Controllers\WhatsAppAccountController::class, 'destroy'])->name('destroy');
        });

        // Templates
        Route::prefix('templates')->name('templates.')->group(function () {
            Route::get('/', [\App\Http\Controllers\WhatsAppTemplateController::class, 'index'])->name('index');
            Route::get('/create', [\App\Http\Controllers\WhatsAppTemplateController::class, 'create'])->name('create');
            Route::post('/', [\App\Http\Controllers\WhatsAppTemplateController::class, 'store'])->name('store');
            Route::post('/sync/{account}', [\App\Http\Controllers\WhatsAppTemplateController::class, 'sync'])->name('sync');
        });

        // Campaigns
        Route::prefix('campaigns')->name('campaigns.')->group(function () {
            Route::get('/', [\App\Http\Controllers\WhatsAppCampaignController::class, 'index'])->name('index');
            Route::get('/create', [\App\Http\Controllers\WhatsAppCampaignController::class, 'create'])->name('create');
            Route::post('/', [\App\Http\Controllers\WhatsAppCampaignController::class, 'store'])->name('store');
            Route::post('/{campaign}/send', [\App\Http\Controllers\WhatsAppCampaignController::class, 'send'])->name('send');
        });

        // Conversations / Live Chat
        Route::prefix('conversations')->name('conversations.')->group(function () {
            Route::get('/', [\App\Http\Controllers\WhatsAppConversationController::class, 'index'])->name('index');
            Route::get('/{conversation}', [\App\Http\Controllers\WhatsAppConversationController::class, 'show'])->name('show');
            Route::post('/{conversation}/reply', [\App\Http\Controllers\WhatsAppConversationController::class, 'reply'])->name('reply');
            Route::post('/initiate', [\App\Http\Controllers\WhatsAppConversationController::class, 'initiate'])->name('initiate');
        });

        // Engagement Analytics
        Route::get('/analytics', [\App\Http\Controllers\WhatsAppAnalyticsController::class, 'index'])->name('analytics');

        // Settings
        Route::get('/settings', [\App\Http\Controllers\WhatsAppSettingsController::class, 'index'])->name('settings');
    });

    // Profile
    Route::get('/profile', [\App\Http\Controllers\ProfileController::class, 'index'])->name('profile.index');
    Route::put('/profile', [\App\Http\Controllers\ProfileController::class, 'update'])->name('profile.update');

    // Super Admin Routes
    Route::middleware(['super_admin'])->prefix('super')->name('super.')->group(function () {
        Route::get('/dashboard', [\App\Http\Controllers\SuperAdminController::class, 'index'])->name('dashboard');
        Route::get('/users', [\App\Http\Controllers\SuperAdminController::class, 'users'])->name('users');
        Route::get('/settings', [\App\Http\Controllers\SuperAdminController::class, 'settings'])->name('settings');
        Route::post('/settings', [\App\Http\Controllers\SuperAdminController::class, 'updateSettings'])->name('settings.update');
    });
});

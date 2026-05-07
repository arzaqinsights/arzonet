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
        Route::post('/{emailList}/import-more', [EmailListController::class, 'importMore'])->name('import-more');
        Route::post('/{emailList}/undo-import/{logId}', [EmailListController::class, 'undoImport'])->name('undo-import');
        Route::post('/{emailList}/scrub', [EmailListController::class, 'scrubList'])->name('scrub');
        Route::post('/{emailList}/bulk-action', [EmailListController::class, 'bulkAction'])->name('bulk-action');
        Route::get('/{emailList}/export', [EmailListController::class, 'exportContacts'])->name('export');
        Route::get('/{emailList}/fix-invalid', [EmailListController::class, 'fixInvalid'])->name('fix-invalid');
        Route::post('/{emailList}/save-invalid', [EmailListController::class, 'saveInvalid'])->name('save-invalid');
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
        Route::delete('/{template}', [TemplateController::class, 'destroy'])->name('destroy');
    });

    // Campaigns
    Route::prefix('campaigns')->name('campaigns.')->group(function () {
        Route::get('/', [CampaignController::class, 'index'])->name('index');
        Route::get('/create', [CampaignController::class, 'create'])->name('create');
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
        Route::post('/preview', [CampaignController::class, 'preview'])->name('preview');
        Route::delete('/{campaign}', [CampaignController::class, 'destroy'])->name('destroy');
    });

    // Senders
    Route::prefix('senders')->name('senders.')->group(function () {
        Route::get('/', [SenderController::class, 'index'])->name('index');
        Route::post('/', [SenderController::class, 'store'])->name('store');
        Route::delete('/{sender}', [SenderController::class, 'destroy'])->name('destroy');
        Route::post('/{sender}/retry', [SenderController::class, 'retry'])->name('retry');
        Route::post('/{sender}/test', [SenderController::class, 'testConnection'])->name('test');
    });

    // Blacklist
    Route::prefix('blacklist')->name('blacklist.')->group(function () {
        Route::get('/', [BlacklistController::class, 'index'])->name('index');
        Route::post('/', [BlacklistController::class, 'store'])->name('store');
        Route::post('/bulk', [BlacklistController::class, 'bulkStore'])->name('bulk-store');
        Route::delete('/{blacklistedEmail}', [BlacklistController::class, 'destroy'])->name('destroy');
    });

    // Settings
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');

    // Users
    Route::prefix('users')->name('users.')->group(function () {
        Route::get('/', [UserController::class, 'index'])->name('index');
        Route::post('/', [UserController::class, 'store'])->name('store');
        Route::put('/{user}', [UserController::class, 'update'])->name('update');
        Route::delete('/{user}', [UserController::class, 'destroy'])->name('destroy');
    });

    // Media
    Route::post('/media/upload', [MediaController::class, 'upload'])->name('media.upload');
    Route::get('/media', [MediaController::class, 'index'])->name('media.index');
});

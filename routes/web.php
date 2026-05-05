<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmailListController;
use App\Http\Controllers\TemplateController;
use App\Http\Controllers\CampaignController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\BlacklistController;
use App\Http\Controllers\SenderController;
use App\Http\Controllers\UnsubscribeController;
use App\Http\Controllers\WebhookController;
use App\Http\Controllers\TrackingController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\MediaController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Unsubscribe (Public)
Route::get('/unsubscribe/{id}', [UnsubscribeController::class, 'show'])->name('unsubscribe');
Route::post('/unsubscribe/{id}/confirm', [UnsubscribeController::class, 'confirm'])->name('unsubscribe.confirm');
Route::post('/webhooks/ses', [WebhookController::class, 'handleSES'])->name('webhooks.ses');

// Dashboard
Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

// Tracking (Public)
Route::get('/t/o/{logId}', [TrackingController::class, 'open'])->name('track.open');
Route::get('/t/c/{logId}', [TrackingController::class, 'click'])->name('track.click');

// CRM Contacts
Route::prefix('contacts')->name('contacts.')->group(function () {
    Route::get('/',               [ContactController::class, 'index'])->name('index');
    Route::get('/{email}',        [ContactController::class, 'show'])->name('show');
    Route::post('/{email}/notes', [ContactController::class, 'addNote'])->name('notes.store');
    Route::post('/{email}/tags',  [ContactController::class, 'updateTags'])->name('tags.update');
});

// Email Lists
Route::prefix('email-lists')->name('email-lists.')->group(function () {
    Route::get('/',               [EmailListController::class, 'index'])->name('index');
    Route::get('/create',         [EmailListController::class, 'create'])->name('create');
    Route::post('/',              [EmailListController::class, 'store'])->name('store');
    Route::get('/{emailList}',    [EmailListController::class, 'show'])->name('show');
    Route::post('/{id}/mapping',  [EmailListController::class, 'storeMapping'])->name('store-mapping');
    Route::get('/{emailList}/status',  [EmailListController::class, 'checkStatus'])->name('status');
    Route::post('/{emailList}/filter', [EmailListController::class, 'filterEmails'])->name('filter');
    Route::get('/{emailList}/emails/{emailId}', [EmailListController::class, 'getEmail'])->name('get-email');
    Route::put('/{emailList}/emails/{emailId}', [EmailListController::class, 'updateEmail'])->name('update-email');
    Route::delete('/{emailList}/emails/{emailId}', [EmailListController::class, 'destroyEmail'])->name('destroy-email');
    Route::post('/{emailList}/add-contact', [EmailListController::class, 'addContact'])->name('add-contact');
    Route::post('/{emailList}/import-more', [EmailListController::class, 'importMore'])->name('import-more');
    Route::delete('/{emailList}', [EmailListController::class, 'destroy'])->name('destroy');
});

// Templates
Route::prefix('templates')->name('templates.')->group(function () {
    Route::get('/',                  [TemplateController::class, 'index'])->name('index');
    Route::get('/create',            [TemplateController::class, 'create'])->name('create');
    Route::post('/',                 [TemplateController::class, 'store'])->name('store');
    Route::get('/{template}/edit',   [TemplateController::class, 'edit'])->name('edit');
    Route::put('/{template}',        [TemplateController::class, 'update'])->name('update');
    Route::get('/{template}/preview',[TemplateController::class, 'preview'])->name('preview');
    Route::post('/{template}/test',  [TemplateController::class, 'sendTest'])->name('send-test');
    Route::delete('/{template}',     [TemplateController::class, 'destroy'])->name('destroy');
});

// Campaigns
Route::prefix('campaigns')->name('campaigns.')->group(function () {
    Route::get('/',                    [CampaignController::class, 'index'])->name('index');
    Route::get('/create',              [CampaignController::class, 'create'])->name('create');
    Route::post('/',                   [CampaignController::class, 'store'])->name('store');
    Route::get('/{campaign}',          [CampaignController::class, 'show'])->name('show');
    Route::post('/{campaign}/send',    [CampaignController::class, 'send'])->name('send');
    Route::post('/{campaign}/pause',   [CampaignController::class, 'pause'])->name('pause');
    Route::post('/{campaign}/resume',  [CampaignController::class, 'resume'])->name('resume');
    Route::post('/{campaign}/cancel',  [CampaignController::class, 'cancel'])->name('cancel');
    Route::post('/{campaign}/retry-failed', [CampaignController::class, 'retryFailed'])->name('retry-failed');
    Route::get('/{campaign}/report',   [CampaignController::class, 'report'])->name('report');
    Route::get('/{campaign}/status',   [CampaignController::class, 'checkStatus'])->name('status');
    Route::post('/preview',            [CampaignController::class, 'preview'])->name('preview');
    Route::delete('/{campaign}',       [CampaignController::class, 'destroy'])->name('destroy');
});

// Senders
Route::resource('senders', SenderController::class)->except(['create', 'show', 'edit', 'update']);
Route::post('/senders/{sender}/retry', [SenderController::class, 'retry'])->name('senders.retry');
Route::post('/senders/{sender}/test',  [SenderController::class, 'testConnection'])->name('senders.test');
// Media Management
Route::post('/media/upload', [MediaController::class, 'upload'])->name('media.upload');
Route::get('/media',         [MediaController::class, 'index'])->name('media.index');
// Users (Team Management)
Route::resource('users', UserController::class)->except(['create', 'show', 'edit']);

// Settings
Route::get('/settings',    [SettingsController::class, 'index'])->name('settings.index');
Route::put('/settings',    [SettingsController::class, 'update'])->name('settings.update');

// Blacklist
Route::prefix('blacklist')->name('blacklist.')->group(function () {
    Route::get('/',                            [BlacklistController::class, 'index'])->name('index');
    Route::post('/',                           [BlacklistController::class, 'store'])->name('store');
    Route::post('/bulk',                       [BlacklistController::class, 'bulkStore'])->name('bulk-store');
    Route::delete('/{blacklistedEmail}',        [BlacklistController::class, 'destroy'])->name('destroy');
});

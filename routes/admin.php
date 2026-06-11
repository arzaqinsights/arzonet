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

    // Switch Workspace
    Route::get('/switch-workspace/{id}', [EmailListController::class, 'switchWorkspace'])->name('switch-workspace');

    // Email Lists
    Route::prefix('email-lists')->name('email-lists.')->group(function () {
        Route::get('/', [EmailListController::class, 'index'])->name('index')->middleware('permission:crm.view');
        Route::get('/create', [EmailListController::class, 'create'])->name('create')->middleware('permission:crm.create');
        Route::post('/', [EmailListController::class, 'store'])->name('store')->middleware('permission:crm.create');
        Route::get('/{emailList}', [EmailListController::class, 'show'])->name('show')->middleware('permission:crm.view');
        Route::post('/{id}/mapping', [EmailListController::class, 'storeMapping'])->name('store-mapping')->middleware('permission:crm.import');
        Route::get('/{emailList}/status', [EmailListController::class, 'checkStatus'])->name('status')->middleware('permission:crm.view');
        Route::post('/{emailList}/filter', [EmailListController::class, 'filterEmails'])->name('filter')->middleware('permission:crm.view');
        Route::get('/{emailList}/emails/{emailId}', [EmailListController::class, 'getEmail'])->name('get-email')->middleware('permission:crm.view');
        Route::put('/{emailList}/emails/{emailId}', [EmailListController::class, 'updateEmail'])->name('update-email')->middleware('permission:crm.edit');
        Route::delete('/{emailList}/emails/{emailId}', [EmailListController::class, 'destroyEmail'])->name('destroy-email')->middleware('permission:crm.delete');
        Route::post('/{emailList}/add-contact', [EmailListController::class, 'addContact'])->name('add-contact')->middleware('permission:crm.create');
        Route::post('/{emailList}/add-alternate-channel', [EmailListController::class, 'addAlternateChannel'])->name('add-alternate-channel')->middleware('permission:crm.create');
        Route::post('/{emailList}/add-custom-column', [EmailListController::class, 'addCustomColumn'])->name('add-custom-column')->middleware('permission:crm.edit');
        Route::post('/{emailList}/import-more', [EmailListController::class, 'importMore'])->name('import-more')->middleware('permission:crm.import');
        Route::post('/{emailList}/undo-import/{logId}', [EmailListController::class, 'undoImport'])->name('undo-import')->middleware('permission:crm.import');
        Route::post('/{emailList}/scrub', [EmailListController::class, 'scrubList'])->name('scrub')->middleware('permission:crm.scrub');
        Route::post('/{emailList}/bulk-action', [EmailListController::class, 'bulkAction'])->name('bulk-action')->middleware('permission:crm.bulk');
        Route::get('/{emailList}/export', [EmailListController::class, 'exportContacts'])->name('export')->middleware('permission:crm.export');
        Route::get('/{emailList}/fix-invalid', [EmailListController::class, 'fixInvalid'])->name('fix-invalid')->middleware('permission:crm.scrub');
        Route::delete('/{emailList}/fix-invalid/delete-all', [EmailListController::class, 'deleteAllInvalid'])->name('fix-invalid.delete-all')->middleware('permission:crm.scrub');
        Route::post('/{emailList}/save-invalid', [EmailListController::class, 'saveInvalid'])->name('save-invalid')->middleware('permission:crm.scrub');
        Route::get('/{emailList}/duplicates', [EmailListController::class, 'resolveDuplicates'])->name('duplicates.index')->middleware('permission:crm.bulk');
        Route::post('/{emailList}/duplicates/resolve', [EmailListController::class, 'resolveDuplicatesAction'])->name('duplicates.resolve')->middleware('permission:crm.bulk');
        Route::post('/{emailList}/transfer/{emailId}', [EmailListController::class, 'transferContact'])->name('transfer-contact')->middleware('permission:crm.edit');
        Route::post('/{emailList}/send-to-pipeline/{emailId}', [EmailListController::class, 'sendToPipeline'])->name('send-to-pipeline')->middleware('permission:pipelines.manage');
        Route::patch('/{emailList}/update-name', [EmailListController::class, 'updateName'])->name('update-name')->middleware('permission:crm.edit');
        Route::post('/{emailList}/update-settings', [EmailListController::class, 'updateSettings'])->name('update-settings')->middleware('permission:crm.edit');
        
        // Contact Profile & Merge
        Route::get('/{emailList}/contacts/{emailId}/profile', [EmailListController::class, 'getProfile'])->name('contact.profile')->middleware('permission:crm.view');
        Route::post('/{emailList}/contacts/{emailId}/note', [EmailListController::class, 'addNote'])->name('contact.note')->middleware('permission:crm.edit');
        Route::post('/{emailList}/contacts/{emailId}/task', [EmailListController::class, 'addTask'])->name('contact.task')->middleware('permission:tasks.manage');
        Route::post('/{emailList}/merge-duplicates', [EmailListController::class, 'mergeDuplicates'])->name('merge-duplicates')->middleware('permission:crm.bulk');
        Route::get('/check-mx', [EmailListController::class, 'checkMX'])->name('check-mx')->middleware('permission:crm.scrub');
        Route::delete('/{emailList}', [EmailListController::class, 'destroy'])->name('destroy')->middleware('permission:crm.delete');
    });

    // Subscription Topics
    Route::resource('subscription-topics', \App\Http\Controllers\SubscriptionTopicController::class)->except(['show'])->middleware('permission:crm.edit');

    // Workflows (Automations)
    Route::prefix('workflows')->name('workflows.')->group(function () {
        Route::get('/', [\App\Http\Controllers\WorkflowController::class, 'index'])->name('index')->middleware('permission:workflows.view');
        Route::get('/create', [\App\Http\Controllers\WorkflowController::class, 'create'])->name('create')->middleware('permission:workflows.create');
        Route::post('/', [\App\Http\Controllers\WorkflowController::class, 'store'])->name('store')->middleware('permission:workflows.create');
        Route::get('/{workflow}', [\App\Http\Controllers\WorkflowController::class, 'show'])->name('show')->middleware('permission:workflows.view');
        Route::get('/{workflow}/edit', [\App\Http\Controllers\WorkflowController::class, 'edit'])->name('edit')->middleware('permission:workflows.create');
        Route::put('/{workflow}', [\App\Http\Controllers\WorkflowController::class, 'update'])->name('update')->middleware('permission:workflows.create');
        Route::delete('/{workflow}', [\App\Http\Controllers\WorkflowController::class, 'destroy'])->name('destroy')->middleware('permission:workflows.delete');
    });

    // Templates
    Route::prefix('templates')->name('templates.')->group(function () {
        Route::get('/', [TemplateController::class, 'index'])->name('index')->middleware('permission:templates.view');
        Route::get('/create', [TemplateController::class, 'create'])->name('create')->middleware('permission:templates.create');
        Route::post('/', [TemplateController::class, 'store'])->name('store')->middleware('permission:templates.create');
        Route::get('/{template}/edit', [TemplateController::class, 'edit'])->name('edit')->middleware('permission:templates.edit');
        Route::put('/{template}', [TemplateController::class, 'update'])->name('update')->middleware('permission:templates.edit');
        Route::get('/{template}/preview', [TemplateController::class, 'preview'])->name('preview')->middleware('permission:templates.view');
        Route::post('/{template}/test', [TemplateController::class, 'sendTest'])->name('send-test')->middleware('permission:templates.edit');
        Route::post('/{template}/clone', [TemplateController::class, 'clone'])->name('clone')->middleware('permission:templates.create');
        Route::delete('/{template}', [TemplateController::class, 'destroy'])->name('destroy')->middleware('permission:templates.delete');
    });

    // Campaigns
    Route::prefix('campaigns')->name('campaigns.')->group(function () {
        Route::get('/', [CampaignController::class, 'index'])->name('index')->middleware('permission:campaigns.view');
        Route::get('/create', [CampaignController::class, 'create'])->name('create')->middleware('permission:campaigns.create');
        Route::get('/{campaign}/wizard', [CampaignController::class, 'wizard'])->name('wizard')->middleware('permission:campaigns.create');
        Route::post('/{campaign}/save-step', [CampaignController::class, 'saveStep'])->name('save-step')->middleware('permission:campaigns.create');
        Route::post('/', [CampaignController::class, 'store'])->name('store')->middleware('permission:campaigns.create');
        Route::get('/{campaign}', [CampaignController::class, 'show'])->name('show')->middleware('permission:campaigns.view');
        Route::post('/{campaign}/send', [CampaignController::class, 'send'])->name('send')->middleware('permission:campaigns.send');
        Route::post('/{campaign}/pause', [CampaignController::class, 'pause'])->name('pause')->middleware('permission:campaigns.pause_resume');
        Route::post('/{campaign}/resume', [CampaignController::class, 'resume'])->name('resume')->middleware('permission:campaigns.pause_resume');
        Route::post('/{campaign}/cancel', [CampaignController::class, 'cancel'])->name('cancel')->middleware('permission:campaigns.pause_resume');
        Route::post('/{campaign}/retry-failed', [CampaignController::class, 'retryFailed'])->name('retry-failed')->middleware('permission:campaigns.send');
        Route::get('/{campaign}/report', [CampaignController::class, 'report'])->name('report')->middleware('permission:campaigns.view');
        Route::get('/{campaign}/status', [CampaignController::class, 'checkStatus'])->name('status')->middleware('permission:campaigns.view');
        Route::post('/{campaign}/clone', [CampaignController::class, 'clone'])->name('clone')->middleware('permission:campaigns.create');
        Route::post('/{campaign}/send-test', [CampaignController::class, 'sendTest'])->name('send-test')->middleware('permission:campaigns.send');
        Route::get('/{campaign}/export-logs', [CampaignController::class, 'exportLogs'])->name('export-logs')->middleware('permission:campaigns.view');
        Route::get('/preview', [CampaignController::class, 'preview'])->name('preview')->middleware('permission:campaigns.view');
        Route::get('/{campaign}/edit', [CampaignController::class, 'edit'])->name('edit')->middleware('permission:campaigns.create');
        Route::put('/{campaign}', [CampaignController::class, 'update'])->name('update')->middleware('permission:campaigns.create');
        Route::delete('/{campaign}', [CampaignController::class, 'destroy'])->name('destroy')->middleware('permission:campaigns.delete');
    });

    // Senders
    Route::prefix('senders')->name('senders.')->group(function () {
        Route::get('/', [SenderController::class, 'index'])->name('index')->middleware('permission:senders.view');
        Route::post('/', [SenderController::class, 'store'])->name('store')->middleware('permission:senders.create');
        Route::get('/{sender}/edit', [SenderController::class, 'edit'])->name('edit')->middleware('permission:senders.create');
        Route::put('/{sender}', [SenderController::class, 'update'])->name('update')->middleware('permission:senders.create');
        Route::get('/{sender}/verify', [SenderController::class, 'verify'])->name('verify')->middleware('permission:senders.verify');
        Route::delete('/{sender}', [SenderController::class, 'destroy'])->name('destroy')->middleware('permission:senders.delete');
        Route::post('/{sender}/test', [SenderController::class, 'test'])->name('test')->middleware('permission:senders.verify');
        Route::post('/{sender}/retry', [SenderController::class, 'retry'])->name('retry')->middleware('permission:senders.verify');
    });

    // Blacklist
    Route::prefix('blacklist')->name('blacklist.')->group(function () {
        Route::get('/', [BlacklistController::class, 'index'])->name('index')->middleware('permission:blacklist.manage');
        Route::post('/', [BlacklistController::class, 'store'])->name('store')->middleware('permission:blacklist.manage');
        Route::post('/bulk', [BlacklistController::class, 'bulkStore'])->name('bulk-store')->middleware('permission:blacklist.manage');
        Route::delete('/{blacklistedEmail}', [BlacklistController::class, 'destroy'])->name('destroy')->middleware('permission:blacklist.manage');
    });

    // Domains
    Route::prefix('domains')->name('domains.')->group(function () {
        Route::get('/', [DomainController::class, 'index'])->name('index')->middleware('permission:senders.view');
        Route::post('/', [DomainController::class, 'store'])->name('store')->middleware('permission:senders.create');
        Route::get('/{domain}', [DomainController::class, 'show'])->name('show')->middleware('permission:senders.view');
        Route::post('/{domain}/verify', [DomainController::class, 'verify'])->name('verify')->middleware('permission:senders.verify');
        Route::delete('/{domain}', [DomainController::class, 'destroy'])->name('destroy')->middleware('permission:senders.delete');
    });

    // Settings
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index')->middleware('permission:settings.view');
    Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update')->middleware('permission:settings.update');

    // Billing & Plans
    Route::prefix('billing')->name('billing.')->group(function () {
        Route::get('/plans', [\App\Http\Controllers\PlansController::class, 'index'])->name('plans')->middleware('permission:billing.view');
        Route::post('/purchase', [\App\Http\Controllers\PlansController::class, 'purchase'])->name('purchase')->middleware('permission:billing.purchase');
        Route::get('/payment-return', [\App\Http\Controllers\PlansController::class, 'paymentReturn'])->name('payment-return')->middleware('permission:billing.purchase');
        Route::get('/invoices', [\App\Http\Controllers\InvoicesController::class, 'index'])->name('invoices.index')->middleware('permission:billing.view');
        Route::get('/invoices/{invoice}', [\App\Http\Controllers\InvoicesController::class, 'show'])->name('invoices.show')->middleware('permission:billing.view');
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
            Route::get('/', [\App\Http\Controllers\WhatsAppAccountController::class, 'index'])->name('index')->middleware('permission:whatsapp.accounts');
            Route::post('/', [\App\Http\Controllers\WhatsAppAccountController::class, 'store'])->name('store')->middleware('permission:whatsapp.accounts');
            Route::post('/{account}/register', [\App\Http\Controllers\WhatsAppAccountController::class, 'register'])->name('register')->middleware('permission:whatsapp.accounts');
            Route::delete('/{whatsappAccount}', [\App\Http\Controllers\WhatsAppAccountController::class, 'destroy'])->name('destroy')->middleware('permission:whatsapp.accounts');
        });

        // Templates
        Route::prefix('templates')->name('templates.')->group(function () {
            Route::get('/', [\App\Http\Controllers\WhatsAppTemplateController::class, 'index'])->name('index')->middleware('permission:whatsapp.templates');
            Route::get('/create', [\App\Http\Controllers\WhatsAppTemplateController::class, 'create'])->name('create')->middleware('permission:whatsapp.templates');
            Route::post('/', [\App\Http\Controllers\WhatsAppTemplateController::class, 'store'])->name('store')->middleware('permission:whatsapp.templates');
            Route::post('/sync/{account}', [\App\Http\Controllers\WhatsAppTemplateController::class, 'sync'])->name('sync')->middleware('permission:whatsapp.templates');
        });

        // Campaigns
        Route::prefix('campaigns')->name('campaigns.')->group(function () {
            Route::get('/', [\App\Http\Controllers\WhatsAppCampaignController::class, 'index'])->name('index')->middleware('permission:whatsapp.campaigns');
            Route::get('/create', [\App\Http\Controllers\WhatsAppCampaignController::class, 'create'])->name('create')->middleware('permission:whatsapp.campaigns');
            Route::post('/', [\App\Http\Controllers\WhatsAppCampaignController::class, 'store'])->name('store')->middleware('permission:whatsapp.campaigns');
            Route::post('/{campaign}/send', [\App\Http\Controllers\WhatsAppCampaignController::class, 'send'])->name('send')->middleware('permission:whatsapp.campaigns');
        });

        // Conversations / Live Chat
        Route::prefix('conversations')->name('conversations.')->group(function () {
            Route::get('/', [\App\Http\Controllers\WhatsAppConversationController::class, 'index'])->name('index')->middleware('permission:whatsapp.view');
            Route::get('/{conversation}', [\App\Http\Controllers\WhatsAppConversationController::class, 'show'])->name('show')->middleware('permission:whatsapp.view');
            Route::post('/{conversation}/reply', [\App\Http\Controllers\WhatsAppConversationController::class, 'reply'])->name('reply')->middleware('permission:whatsapp.chat.reply');
            Route::post('/initiate', [\App\Http\Controllers\WhatsAppConversationController::class, 'initiate'])->name('initiate')->middleware('permission:whatsapp.chat.reply');
        });

        // Engagement Analytics
        Route::get('/analytics', [\App\Http\Controllers\WhatsAppAnalyticsController::class, 'index'])->name('analytics')->middleware('permission:whatsapp.view');

        // Settings
        Route::get('/settings', [\App\Http\Controllers\WhatsAppSettingsController::class, 'index'])->name('settings')->middleware('permission:settings.view');
    });

    // ──────────────────────────────────────────────────────
    // CRM: Pipelines (Deals Board)
    // ──────────────────────────────────────────────────────
    Route::prefix('pipelines')->name('pipelines.')->group(function () {
        Route::get('/', [\App\Http\Controllers\PipelineController::class, 'index'])->name('index')->middleware('permission:pipelines.view');
        Route::get('/create', [\App\Http\Controllers\PipelineController::class, 'create'])->name('create')->middleware('permission:pipelines.manage');
        Route::post('/', [\App\Http\Controllers\PipelineController::class, 'store'])->name('store')->middleware('permission:pipelines.manage');
        Route::get('/{pipeline}', [\App\Http\Controllers\PipelineController::class, 'show'])->name('show')->middleware('permission:pipelines.view');
        Route::put('/{pipeline}/settings', [\App\Http\Controllers\PipelineController::class, 'updateSettings'])->name('update-settings')->middleware('permission:pipelines.manage');
        Route::put('/{pipeline}/transfer', [\App\Http\Controllers\PipelineController::class, 'transferOwnership'])->name('transfer-ownership')->middleware('permission:pipelines.manage');
        Route::delete('/{pipeline}', [\App\Http\Controllers\PipelineController::class, 'destroy'])->name('destroy')->middleware('permission:pipelines.manage');

        // Stage Management
        Route::post('/{pipeline}/stages', [\App\Http\Controllers\PipelineController::class, 'addStage'])->name('stages.store')->middleware('permission:pipelines.manage');
        Route::put('/stages/{stage}', [\App\Http\Controllers\PipelineController::class, 'updateStage'])->name('stages.update')->middleware('permission:pipelines.manage');
        Route::delete('/stages/{stage}', [\App\Http\Controllers\PipelineController::class, 'deleteStage'])->name('stages.destroy')->middleware('permission:pipelines.manage');
        Route::post('/{pipeline}/stages/reorder', [\App\Http\Controllers\PipelineController::class, 'reorderStages'])->name('stages.reorder')->middleware('permission:pipelines.manage');

        // Deals
        Route::post('/deals/move', [\App\Http\Controllers\PipelineController::class, 'updateDealStage'])->name('deals.move')->middleware('permission:pipelines.manage');
        Route::post('/{pipeline}/deals', [\App\Http\Controllers\PipelineController::class, 'storeDeal'])->name('deals.store')->middleware('permission:pipelines.manage');
        Route::put('/deals/{deal}', [\App\Http\Controllers\PipelineController::class, 'updateDeal'])->name('deals.update')->middleware('permission:pipelines.manage');
        Route::delete('/deals/{deal}', [\App\Http\Controllers\PipelineController::class, 'destroyDeal'])->name('deals.destroy')->middleware('permission:pipelines.manage');

        // Deal Activities
        Route::get('/deals/{deal}/activities', [\App\Http\Controllers\PipelineController::class, 'dealActivities'])->name('deals.activities')->middleware('permission:pipelines.view');

        // Deal Tasks & Comments
        Route::get('/deals/{deal}/tasks', [\App\Http\Controllers\PipelineController::class, 'dealTasks'])->name('deals.tasks')->middleware('permission:pipelines.view');
        Route::post('/deals/{deal}/tasks', [\App\Http\Controllers\PipelineController::class, 'storeDealTask'])->name('deals.tasks.store')->middleware('permission:tasks.manage');
        Route::post('/deals/tasks/{task}/toggle', [\App\Http\Controllers\PipelineController::class, 'toggleDealTask'])->name('deals.tasks.toggle')->middleware('permission:tasks.manage');
        Route::delete('/deals/tasks/{task}', [\App\Http\Controllers\PipelineController::class, 'destroyDealTask'])->name('deals.tasks.destroy')->middleware('permission:tasks.manage');
        Route::post('/deals/{deal}/comments', [\App\Http\Controllers\PipelineController::class, 'storeDealComment'])->name('deals.comments.store')->middleware('permission:pipelines.manage');

        // Analytics
        Route::get('/{pipeline}/analytics', [\App\Http\Controllers\PipelineController::class, 'analytics'])->name('analytics')->middleware('permission:pipelines.view');
    });

    // ──────────────────────────────────────────────────────
    // CRM: Segment Builder
    // ──────────────────────────────────────────────────────
    Route::prefix('segments')->name('segments.')->group(function () {
        Route::get('/', [\App\Http\Controllers\SegmentBuilderController::class, 'index'])->name('index')->middleware('permission:segments.view');
        Route::get('/create', [\App\Http\Controllers\SegmentBuilderController::class, 'create'])->name('create')->middleware('permission:segments.manage');
        Route::post('/', [\App\Http\Controllers\SegmentBuilderController::class, 'store'])->name('store')->middleware('permission:segments.manage');
        Route::post('/preview', [\App\Http\Controllers\SegmentBuilderController::class, 'preview'])->name('preview')->middleware('permission:segments.view');
        Route::get('/{segment}', [\App\Http\Controllers\SegmentBuilderController::class, 'show'])->name('show')->middleware('permission:segments.view');
        Route::get('/{segment}/edit', [\App\Http\Controllers\SegmentBuilderController::class, 'edit'])->name('edit')->middleware('permission:segments.manage');
        Route::put('/{segment}', [\App\Http\Controllers\SegmentBuilderController::class, 'update'])->name('update')->middleware('permission:segments.manage');
        Route::delete('/{segment}', [\App\Http\Controllers\SegmentBuilderController::class, 'destroy'])->name('destroy')->middleware('permission:segments.manage');
    });

    // ──────────────────────────────────────────────────────
    // CRM: Tasks & Calendar
    // ──────────────────────────────────────────────────────
    Route::prefix('tasks')->name('tasks.')->group(function () {
        Route::get('/', [\App\Http\Controllers\TaskController::class, 'index'])->name('index')->middleware('permission:tasks.view');
        Route::post('/', [\App\Http\Controllers\TaskController::class, 'store'])->name('store')->middleware('permission:tasks.manage');
        Route::put('/{task}', [\App\Http\Controllers\TaskController::class, 'update'])->name('update')->middleware('permission:tasks.manage');
        Route::post('/{task}/toggle', [\App\Http\Controllers\TaskController::class, 'toggle'])->name('toggle')->middleware('permission:tasks.manage');
        Route::delete('/{task}', [\App\Http\Controllers\TaskController::class, 'destroy'])->name('destroy')->middleware('permission:tasks.manage');
        Route::get('/calendar-events', [\App\Http\Controllers\TaskController::class, 'calendarEvents'])->name('calendar-events')->middleware('permission:tasks.view');
    });

    // ──────────────────────────────────────────────────────
    // CRM: Custom Fields
    // ──────────────────────────────────────────────────────
    Route::prefix('custom-fields')->name('custom-fields.')->group(function () {
        Route::get('/', [\App\Http\Controllers\CustomFieldController::class, 'index'])->name('index')->middleware('permission:custom_fields.manage');
        Route::post('/', [\App\Http\Controllers\CustomFieldController::class, 'store'])->name('store')->middleware('permission:custom_fields.manage');
        Route::delete('/{customField}', [\App\Http\Controllers\CustomFieldController::class, 'destroy'])->name('destroy')->middleware('permission:custom_fields.manage');
    });

    // CRM: Contacts Profile
    Route::prefix('contacts')->name('contacts.')->group(function () {
        Route::get('/', [\App\Http\Controllers\ContactController::class, 'index'])->name('index')->middleware('permission:crm.view');
        Route::get('/{email}', [\App\Http\Controllers\ContactController::class, 'show'])->name('show')->middleware('permission:crm.view');
        Route::post('/{email}/notes', [\App\Http\Controllers\ContactController::class, 'addNote'])->name('notes.store')->middleware('permission:crm.edit');
        Route::post('/{email}/tags', [\App\Http\Controllers\ContactController::class, 'updateTags'])->name('tags.update')->middleware('permission:crm.edit');
        Route::post('/{email}/topics', [\App\Http\Controllers\ContactController::class, 'updateTopics'])->name('topics.update')->middleware('permission:crm.edit');
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

    // ──────────────────────────────────────────────────────
    // CRM: Tags Management
    // ──────────────────────────────────────────────────────
    Route::prefix('tags')->name('tags.')->group(function () {
        Route::get('/', [\App\Http\Controllers\TagController::class, 'index'])->name('index')->middleware('permission:crm.view');
        Route::post('/rename', [\App\Http\Controllers\TagController::class, 'rename'])->name('rename')->middleware('permission:crm.edit');
        Route::post('/merge', [\App\Http\Controllers\TagController::class, 'merge'])->name('merge')->middleware('permission:crm.edit');
        Route::post('/delete', [\App\Http\Controllers\TagController::class, 'delete'])->name('delete')->middleware('permission:crm.edit');
    });

    // ──────────────────────────────────────────────────────
    // CRM: Signup Forms Builder
    // ──────────────────────────────────────────────────────
    Route::prefix('signup-forms')->name('signup-forms.')->group(function () {
        Route::get('/', [\App\Http\Controllers\SignupFormController::class, 'index'])->name('index')->middleware('permission:crm.view');
        Route::get('/create', [\App\Http\Controllers\SignupFormController::class, 'create'])->name('create')->middleware('permission:crm.edit');
        Route::post('/', [\App\Http\Controllers\SignupFormController::class, 'store'])->name('store')->middleware('permission:crm.edit');
        Route::get('/{signupForm}/edit', [\App\Http\Controllers\SignupFormController::class, 'edit'])->name('edit')->middleware('permission:crm.edit');
        Route::put('/{signupForm}', [\App\Http\Controllers\SignupFormController::class, 'update'])->name('update')->middleware('permission:crm.edit');
        Route::delete('/{signupForm}', [\App\Http\Controllers\SignupFormController::class, 'destroy'])->name('destroy')->middleware('permission:crm.edit');
    });

    // ──────────────────────────────────────────────────────
    // CRM: Audience Insights
    // ──────────────────────────────────────────────────────
    Route::get('/insights', [\App\Http\Controllers\AudienceInsightsController::class, 'index'])->name('insights.index')->middleware('permission:crm.view');

    // ──────────────────────────────────────────────────────
    // CRM: Sales Email Sequences (Drip Campaigns)
    // ──────────────────────────────────────────────────────
    Route::prefix('sequences')->name('sequences.')->group(function () {
        Route::get('/', [\App\Http\Controllers\SequenceController::class, 'index'])->name('index')->middleware('permission:campaigns.view');
        Route::get('/create', [\App\Http\Controllers\SequenceController::class, 'create'])->name('create')->middleware('permission:campaigns.manage');
        Route::post('/', [\App\Http\Controllers\SequenceController::class, 'store'])->name('store')->middleware('permission:campaigns.manage');
        Route::get('/{sequence}', [\App\Http\Controllers\SequenceController::class, 'show'])->name('show')->middleware('permission:campaigns.view');
        Route::get('/{sequence}/edit', [\App\Http\Controllers\SequenceController::class, 'edit'])->name('edit')->middleware('permission:campaigns.manage');
        Route::put('/{sequence}', [\App\Http\Controllers\SequenceController::class, 'update'])->name('update')->middleware('permission:campaigns.manage');
        Route::delete('/{sequence}', [\App\Http\Controllers\SequenceController::class, 'destroy'])->name('destroy')->middleware('permission:campaigns.manage');
        
        // Sequence Steps
        Route::post('/{sequence}/steps', [\App\Http\Controllers\SequenceController::class, 'storeStep'])->name('steps.store')->middleware('permission:campaigns.manage');
        Route::put('/steps/{step}', [\App\Http\Controllers\SequenceController::class, 'updateStep'])->name('steps.update')->middleware('permission:campaigns.manage');
        Route::delete('/steps/{step}', [\App\Http\Controllers\SequenceController::class, 'destroyStep'])->name('steps.destroy')->middleware('permission:campaigns.manage');
        
        // Enroll / Unenroll contacts
        Route::post('/{sequence}/enroll', [\App\Http\Controllers\SequenceController::class, 'enrollContact'])->name('enroll')->middleware('permission:crm.edit');
        Route::post('/{sequence}/unenroll', [\App\Http\Controllers\SequenceController::class, 'unenrollContact'])->name('unenroll')->middleware('permission:crm.edit');
    });

    // ──────────────────────────────────────────────────────
    // CRM: Win/Loss Forecasting & Reports
    // ──────────────────────────────────────────────────────
    Route::get('/crm-reports', [\App\Http\Controllers\CRMReportController::class, 'index'])->name('crm-reports.index')->middleware('permission:pipelines.view');
});

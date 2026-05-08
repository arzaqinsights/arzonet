<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SnsController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::post('/sns/webhook', [SnsController::class, 'handle']);
Route::post('/sendgrid/webhook', [\App\Http\Controllers\SendGridWebhookController::class, 'handle']);
Route::get('/email-statuses', [SnsController::class, 'index']);


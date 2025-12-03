<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\CampaignController;
use App\Http\Controllers\Api\TaskController;
use App\Http\Controllers\Api\BacklinkController;
use App\Http\Controllers\Api\SiteAccountController;
use App\Http\Controllers\Api\ProxyController;
use App\Http\Controllers\Api\LLMController;
use App\Http\Controllers\Api\CaptchaController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group and the "/api" prefix.
|
*/

// API routes with rate limiting
// Rate limit: 60 requests per minute per IP
Route::middleware(['api', 'throttle:60,1'])->group(function () {
    // Campaign endpoints
    Route::get('campaigns', [CampaignController::class, 'index']);
    Route::get('campaigns/{id}', [CampaignController::class, 'show']);
    
    // Task endpoints (for Python workers)
    // Higher rate limit for worker endpoints: 120 requests per minute
    Route::prefix('tasks')->middleware('throttle:120,1')->group(function () {
        Route::get('/pending', [TaskController::class, 'getPendingTasks']);
        Route::post('/{id}/lock', [TaskController::class, 'lockTask']);
        Route::post('/{id}/unlock', [TaskController::class, 'unlockTask']);
        Route::put('/{id}/status', [TaskController::class, 'updateTaskStatus']);
    });
    
    // Backlink endpoints (for Python workers)
    Route::prefix('backlinks')->group(function () {
        Route::post('/', [BacklinkController::class, 'store']);
        Route::put('/{id}', [BacklinkController::class, 'update']);
    });
    
    // Site account endpoints (for Python workers)
    Route::prefix('site-accounts')->group(function () {
        Route::post('/', [SiteAccountController::class, 'store']);
        Route::put('/{id}', [SiteAccountController::class, 'update']);
    });
    
    // Proxy endpoints (for Python workers)
    Route::get('/proxies', [ProxyController::class, 'index']);
    
    // LLM Content Generation
    // Lower rate limit for expensive operations: 30 requests per minute
    Route::post('/llm/generate', [LLMController::class, 'generate'])->middleware('throttle:30,1');
    
    // Captcha Solving
    // Lower rate limit for expensive operations: 30 requests per minute
    Route::post('/captcha/solve', [CaptchaController::class, 'solve'])->middleware('throttle:30,1');
});

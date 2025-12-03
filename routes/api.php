<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\CampaignController;
use App\Http\Controllers\Api\TaskController;
use App\Http\Controllers\Api\BacklinkController;
use App\Http\Controllers\Api\SiteAccountController;
use App\Http\Controllers\Api\ProxyController;

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

Route::middleware('api')->group(function () {
    // Campaign endpoints
    Route::get('campaigns', [CampaignController::class, 'index']);
    Route::get('campaigns/{id}', [CampaignController::class, 'show']);
    
    // Task endpoints (for Python workers)
    Route::prefix('tasks')->group(function () {
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
});

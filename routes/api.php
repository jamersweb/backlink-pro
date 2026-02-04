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
use App\Http\Controllers\Api\OpportunityController;
use App\Http\Controllers\Api\MLController;
use App\Http\Controllers\Api\PlanController;
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

// ============================================================================
// PUBLIC API Routes (no authentication required)
// ============================================================================
Route::middleware(['api', 'throttle:30,1'])->group(function () {
    // Plans - public access for pricing page
    Route::get('/plans', [PlanController::class, 'index'])->name('api.plans.index');
    Route::get('/plans/{code}', [PlanController::class, 'show'])->name('api.plans.show');
    
    // Marketing/Lead capture endpoints
    Route::post('/plan/preview', [PlanController::class, 'preview'])->name('api.plan.preview');
    Route::post('/plan/lead', [PlanController::class, 'lead'])->name('api.plan.lead');
});

// ============================================================================
// INTERNAL API Routes (require API token authentication)
// Used by Python workers and internal services
// ============================================================================
Route::middleware(['api', 'api.token'])->group(function () {
    
    // Task endpoints (for Python workers) - Higher rate limit
    Route::prefix('tasks')->middleware('throttle:1000,1')->group(function () {
        Route::get('/pending', [TaskController::class, 'getPendingTasks']);
        Route::post('/{id}/lock', [TaskController::class, 'lockTask']);
        Route::post('/{id}/unlock', [TaskController::class, 'unlockTask']);
        Route::put('/{id}/status', [TaskController::class, 'updateTaskStatus']);
    });
    
    // Standard rate limited endpoints
    Route::middleware('throttle:60,1')->group(function () {
        // Campaign endpoints (now secured)
        Route::get('campaigns', [CampaignController::class, 'index']);
        Route::get('campaigns/{id}', [CampaignController::class, 'show']);
        
        // Opportunity endpoints (now secured)
        Route::get('opportunities/for-campaign/{campaign_id}', [OpportunityController::class, 'getForCampaign']);
        
        // Backlink endpoints
        Route::prefix('backlinks')->group(function () {
            Route::post('/', [BacklinkController::class, 'store']);
            Route::put('/{id}', [BacklinkController::class, 'update']);
        });
        
        // Site account endpoints
        Route::prefix('site-accounts')->group(function () {
            Route::post('/', [SiteAccountController::class, 'store']);
            Route::put('/{id}', [SiteAccountController::class, 'update']);
        });
        
        // Proxy endpoints
        Route::get('/proxies', [ProxyController::class, 'index']);
        
        // ML/AI endpoints
        Route::prefix('ml')->group(function () {
            Route::get('/historical-data', [MLController::class, 'getHistoricalData']);
            Route::get('/action-recommendation/{campaign_id}', [MLController::class, 'getActionRecommendation']);
        });
    });
    
    // Expensive operations - Lower rate limit (30/min)
    Route::middleware('throttle:30,1')->group(function () {
        Route::post('/llm/generate', [LLMController::class, 'generate']);
        Route::post('/captcha/solve', [CaptchaController::class, 'solve']);
    });
});

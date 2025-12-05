<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\LocationController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\LeadsController;
use App\Http\Controllers\Admin\UsersController;
use App\Http\Controllers\Admin\PlansController;
use App\Http\Controllers\Admin\SubscriptionController;
use App\Http\Controllers\ProfileController;

// Note: prefix('admin'), middleware(['auth', 'role:admin']), and name('admin.') 
// are already applied in bootstrap/app.php, so we don't need to add them here
Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

// Leads Management
Route::prefix('leads')->name('leads.')->group(function () {
    Route::get('/verified', [LeadsController::class, 'verifiedUsers'])->name('verified');
    Route::get('/non-verified', [LeadsController::class, 'nonVerifiedUsers'])->name('non-verified');
    Route::get('/purchase', [LeadsController::class, 'purchaseUsers'])->name('purchase');
});

// Users Management
Route::get('/users', [UsersController::class, 'index'])->name('users.index');
Route::get('/users/{id}', [UsersController::class, 'show'])->name('users.show');
Route::get('/users/{id}/edit', [UsersController::class, 'edit'])->name('users.edit');
Route::put('/users/{id}', [UsersController::class, 'update'])->name('users.update');
Route::post('/users/{id}/reset-password', [UsersController::class, 'resetPassword'])->name('users.reset-password');

// Plans Management
Route::get('/plans', [PlansController::class, 'index'])->name('plans.index');
Route::get('/plans/create', [PlansController::class, 'create'])->name('plans.create');
Route::post('/plans', [PlansController::class, 'store'])->name('plans.store');
Route::get('/plans/{id}', [PlansController::class, 'show'])->name('plans.show');
Route::get('/plans/{id}/edit', [PlansController::class, 'edit'])->name('plans.edit');
Route::put('/plans/{id}', [PlansController::class, 'update'])->name('plans.update');
Route::delete('/plans/{id}', [PlansController::class, 'destroy'])->name('plans.destroy');

// Subscriptions Management
Route::get('/subscriptions', [SubscriptionController::class, 'index'])->name('subscriptions.index');
Route::get('/subscriptions/{id}', [SubscriptionController::class, 'show'])->name('subscriptions.show');

// country/city/state
Route::get('locations/create', [LocationController::class, 'create'])
     ->name('locations.create');
Route::get('locations/states/{country}', [LocationController::class, 'getStates'])
     ->name('locations.states');
Route::get('locations/cities/{state}', [LocationController::class, 'getCities'])
     ->name('locations.cities');
Route::resource('campaigns', \App\Http\Controllers\Admin\CampaignController::class);
Route::post('/campaigns/{campaign}/create-tasks', [\App\Http\Controllers\Admin\CampaignController::class, 'createTasks'])->name('campaigns.create-tasks');
Route::post('/campaigns/{campaign}/pause', [\App\Http\Controllers\Admin\CampaignController::class, 'pause'])->name('campaigns.pause');
Route::post('/campaigns/{campaign}/resume', [\App\Http\Controllers\Admin\CampaignController::class, 'resume'])->name('campaigns.resume');

// Backlink Categories Management
Route::resource('categories', \App\Http\Controllers\Admin\CategoryController::class)->except(['show']);

// Backlink Opportunities Management
Route::get('/backlink-opportunities', [\App\Http\Controllers\Admin\BacklinkOpportunityController::class, 'index'])->name('backlink-opportunities.index');
Route::get('/backlink-opportunities/create', [\App\Http\Controllers\Admin\BacklinkOpportunityController::class, 'create'])->name('backlink-opportunities.create');
Route::post('/backlink-opportunities', [\App\Http\Controllers\Admin\BacklinkOpportunityController::class, 'store'])->name('backlink-opportunities.store');
Route::get('/backlink-opportunities/{id}/edit', [\App\Http\Controllers\Admin\BacklinkOpportunityController::class, 'edit'])->name('backlink-opportunities.edit');
Route::put('/backlink-opportunities/{id}', [\App\Http\Controllers\Admin\BacklinkOpportunityController::class, 'update'])->name('backlink-opportunities.update');
Route::delete('/backlink-opportunities/{id}', [\App\Http\Controllers\Admin\BacklinkOpportunityController::class, 'destroy'])->name('backlink-opportunities.destroy');
Route::post('/backlink-opportunities/bulk-import', [\App\Http\Controllers\Admin\BacklinkOpportunityController::class, 'bulkImport'])->name('backlink-opportunities.bulk-import');
Route::get('/backlink-opportunities/export', [\App\Http\Controllers\Admin\BacklinkOpportunityController::class, 'export'])->name('backlink-opportunities.export');

// Backlinks Management (actual created backlinks)
Route::get('/backlinks', [\App\Http\Controllers\Admin\BacklinkController::class, 'index'])->name('backlinks.index');
Route::post('/backlinks', [\App\Http\Controllers\Admin\BacklinkController::class, 'store'])->name('backlinks.store');
Route::post('/backlinks/bulk-import', [\App\Http\Controllers\Admin\BacklinkController::class, 'bulkImport'])->name('backlinks.bulk-import');
Route::get('/backlinks/export', [\App\Http\Controllers\Admin\BacklinkController::class, 'export'])->name('backlinks.export');

// Automation Tasks Management
Route::get('/automation-tasks', [\App\Http\Controllers\Admin\AutomationTaskController::class, 'index'])->name('automation-tasks.index');
Route::get('/automation-tasks/{task}', [\App\Http\Controllers\Admin\AutomationTaskController::class, 'show'])->name('automation-tasks.show');
Route::post('/automation-tasks/{task}/retry', [\App\Http\Controllers\Admin\AutomationTaskController::class, 'retry'])->name('automation-tasks.retry');
Route::post('/automation-tasks/{task}/cancel', [\App\Http\Controllers\Admin\AutomationTaskController::class, 'cancel'])->name('automation-tasks.cancel');

// Proxy Management
Route::get('/proxies', [\App\Http\Controllers\Admin\ProxyController::class, 'index'])->name('proxies.index');
Route::post('/proxies', [\App\Http\Controllers\Admin\ProxyController::class, 'store'])->name('proxies.store');
Route::put('/proxies/{proxy}', [\App\Http\Controllers\Admin\ProxyController::class, 'update'])->name('proxies.update');
Route::delete('/proxies/{proxy}', [\App\Http\Controllers\Admin\ProxyController::class, 'destroy'])->name('proxies.destroy');
Route::post('/proxies/{proxy}/reset-errors', [\App\Http\Controllers\Admin\ProxyController::class, 'resetErrors'])->name('proxies.reset-errors');
Route::post('/proxies/{proxy}/test', [\App\Http\Controllers\Admin\ProxyController::class, 'test'])->name('proxies.test');

// Captcha Logs Management
Route::get('/captcha-logs', [\App\Http\Controllers\Admin\CaptchaLogController::class, 'index'])->name('captcha-logs.index');

// System Health
Route::get('/system-health', [\App\Http\Controllers\Admin\SystemHealthController::class, 'index'])->name('system-health.index');
Route::post('/system-health/failed-jobs/{id}/retry', [\App\Http\Controllers\Admin\SystemHealthController::class, 'retryFailedJob'])->name('system-health.retry-job');
Route::post('/system-health/failed-jobs/flush', [\App\Http\Controllers\Admin\SystemHealthController::class, 'flushFailedJobs'])->name('system-health.flush-jobs');

// Settings Management
Route::get('/settings', [\App\Http\Controllers\Admin\SettingsController::class, 'index'])->name('settings.index');
Route::put('/settings/{group}', [\App\Http\Controllers\Admin\SettingsController::class, 'update'])->name('settings.update');
Route::post('/settings/test-connection', [\App\Http\Controllers\Admin\SettingsController::class, 'testConnection'])->name('settings.test-connection');

// Blocked Sites Management
Route::get('/blocked-sites', [\App\Http\Controllers\Admin\BlockedSiteController::class, 'index'])->name('blocked-sites.index');
Route::post('/blocked-sites', [\App\Http\Controllers\Admin\BlockedSiteController::class, 'store'])->name('blocked-sites.store');
Route::put('/blocked-sites/{id}', [\App\Http\Controllers\Admin\BlockedSiteController::class, 'update'])->name('blocked-sites.update');
Route::post('/blocked-sites/{id}/toggle', [\App\Http\Controllers\Admin\BlockedSiteController::class, 'toggle'])->name('blocked-sites.toggle');
Route::delete('/blocked-sites/{id}', [\App\Http\Controllers\Admin\BlockedSiteController::class, 'destroy'])->name('blocked-sites.destroy');

// Profile (accessible from admin panel)
Route::get('/profile', [ProfileController::class, 'index'])->name('profile');

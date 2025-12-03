<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\LocationController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\LeadsController;
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

// country/city/state
Route::get('locations/create', [LocationController::class, 'create'])
     ->name('locations.create');
Route::get('locations/states/{country}', [LocationController::class, 'getStates'])
     ->name('locations.states');
Route::get('locations/cities/{state}', [LocationController::class, 'getCities'])
     ->name('locations.cities');
Route::resource('campaigns', \App\Http\Controllers\Admin\CampaignController::class);

// Profile (accessible from admin panel)
Route::get('/profile', [ProfileController::class, 'index'])->name('profile');

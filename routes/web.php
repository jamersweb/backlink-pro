<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Admin\LocationController;
use App\Http\Controllers\UserCampaignController;



// Authentication Routes
Route::get('/register', [RegisterController::class, 'show'])->name('register');
Route::post('/register', [RegisterController::class, 'register']);
// login and logout routes
Route::get('/login', [LoginController::class, 'show'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
// Authentication Routes end
//user routes
//dashboard route
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', function () {
        return view('user.dashboard');
    })->name('dashboard');
});
// user campaign route
Route::get('/campaign', [UserCampaignController::class, 'create'])
     ->name('user-campaign.create');

Route::post('/campaign', [UserCampaignController::class, 'store'])
     ->name('user-campaign.store');

//user routes end

// Admin Routes
// admin dashboard route
Route::prefix('admin')
    ->middleware(['auth', 'role:admin']) 
     ->name('admin.')
     ->group(function () {
         Route::get('/dashboard', function () {
             return view('admin.dashboard');
         })->name('dashboard');
// admin dashboard route end
// country city state routes
    Route::get('locations/create', [LocationController::class, 'create'])
         ->name('locations.create');
    Route::get('locations/states/{country}', [LocationController::class, 'getStates'])
         ->name('locations.states');
    Route::get('locations/cities/{state}', [LocationController::class, 'getCities'])
         ->name('locations.cities');
 // country city state routes
     });
// admin routes end
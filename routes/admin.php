<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\LocationController;

Route::prefix('admin')
    ->middleware(['auth', 'role:admin'])
    ->name('admin.')
    ->group(function () {
        Route::get('/dashboard', function () {
            return view('admin.dashboard');
        })->name('dashboard');

        // country/city/state
        Route::get('locations/create', [LocationController::class, 'create'])
             ->name('locations.create');
        Route::get('locations/states/{country}', [LocationController::class, 'getStates'])
             ->name('locations.states');
        Route::get('locations/cities/{state}', [LocationController::class, 'getCities'])
             ->name('locations.cities');
             Route::resource('campaigns', \App\Http\Controllers\Admin\CampaignController::class);
    });

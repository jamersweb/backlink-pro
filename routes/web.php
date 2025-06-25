<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Admin\CountryController;

Route::get('/', function () {
    return view('user.dashboard');
});
Route::get('/campaign', function () {
    return view('user.campaign.user-campaign');
});
// Authentication Routes
Route::get('/register', [RegisterController::class, 'show'])->name('register');
Route::post('/register', [RegisterController::class, 'register']);
// login and logout routes
Route::get('/login', [LoginController::class, 'show'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
// Authentication Routes end

// User Dashboard (authenticated users)
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', function () {
        return view('user.dashboard');
    })->name('dashboard');
});

// Admin Dashboard (only admin role)
Route::prefix('admin')
    ->middleware(['auth', 'role:admin']) 
     ->name('admin.')
     ->group(function () {
         Route::get('/dashboard', function () {
             return view('admin.dashboard');
         })->name('dashboard');
          Route::get('countries', [CountryController::class, 'index'])->name('countries.index');
          Route::post('countries/states',[CountryController::class,'states'])->name('countries.states');
          Route::post('countries/cities',[CountryController::class,'cities'])->name('countries.cities');
     });
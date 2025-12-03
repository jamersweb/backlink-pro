<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\UserCampaignController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GmailOAuthController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\BacklinkController;
use App\Http\Controllers\DomainController;
use App\Http\Controllers\SiteAccountController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\ActivityController;
use App\Http\Controllers\ReportsController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\HelpController;
use App\Http\Controllers\DocumentationController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;



// Authentication Routes
Route::get('/register', [RegisterController::class, 'show'])->name('register');
Route::post('/register', [RegisterController::class, 'register']);
// login and logout routes
Route::get('/login', [LoginController::class, 'show'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// Email Verification Routes (accessible without verified middleware)
use App\Http\Controllers\Auth\EmailVerificationController;
Route::get('/email/verify', [EmailVerificationController::class, 'notice'])
    ->middleware(['auth'])
    ->name('verification.notice');
Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
    ->middleware(['auth', 'signed'])
    ->name('verification.verify');
Route::post('/email/verification-notification', [EmailVerificationController::class, 'resend'])
    ->middleware(['auth', 'throttle:6,1'])
    ->name('verification.send');
// Authentication Routes end

// Public routes
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/pricing', [SubscriptionController::class, 'index'])->name('pricing');
Route::get('/plans', [SubscriptionController::class, 'index'])->name('plans');

// Stripe webhook (no CSRF protection)
Route::post('/stripe/webhook', [SubscriptionController::class, 'webhook'])->name('stripe.webhook');
//user routes
//dashboard route

Route::middleware(['auth', 'verified'])->group(function(){

    // dashboard stays at /dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Gmail OAuth routes
    Route::prefix('gmail')
        ->name('gmail.')
        ->group(function() {
            Route::get('/', [GmailOAuthController::class, 'index'])->name('index');
            Route::prefix('oauth')
                ->group(function() {
                    Route::get('/connect', [GmailOAuthController::class, 'connect'])->name('connect');
                    Route::get('/callback', [GmailOAuthController::class, 'callback'])->name('callback');
                    Route::post('/disconnect/{id}', [GmailOAuthController::class, 'disconnect'])->name('disconnect');
                });
        });

    // Subscription routes
    Route::prefix('subscription')
        ->name('subscription.')
        ->group(function() {
            Route::get('/manage', [SubscriptionController::class, 'manage'])->name('manage');
            Route::post('/cancel', [SubscriptionController::class, 'cancel'])->name('cancel');
            Route::post('/resume', [SubscriptionController::class, 'resume'])->name('resume');
            Route::get('/checkout/{plan}', [SubscriptionController::class, 'checkout'])->name('checkout');
            Route::get('/success', [SubscriptionController::class, 'success'])->name('success');
            Route::get('/cancel-page', [SubscriptionController::class, 'cancelPage'])->name('cancel-page');
        });

  Route::prefix('campaign')
     ->name('user-campaign.')
     ->group(function(){
        Route::get('/',     [UserCampaignController::class,'index'])->name('index');
        Route::get('create',[UserCampaignController::class,'create'])->name('create');
        Route::post('/',    [UserCampaignController::class,'store'])->name('store');
        Route::get('{campaign}',       [UserCampaignController::class,'show'])->name('show');
        Route::get('{campaign}/edit',  [UserCampaignController::class,'edit'])->name('edit');
        Route::put('{campaign}',       [UserCampaignController::class,'update'])->name('update');
        Route::delete('{campaign}',    [UserCampaignController::class,'destroy'])->name('destroy');
        Route::get('{campaign}/backlinks', [BacklinkController::class, 'index'])->name('backlinks');
     });

    // Domain Management
    Route::resource('domains', DomainController::class);

    // Site Account Management
    Route::resource('site-accounts', SiteAccountController::class);

    // Settings
    Route::prefix('settings')
        ->name('settings.')
        ->group(function() {
            Route::get('/', [SettingsController::class, 'index'])->name('index');
            Route::put('/profile', [SettingsController::class, 'updateProfile'])->name('profile.update');
            Route::put('/password', [SettingsController::class, 'updatePassword'])->name('password.update');
        });

    // Activity Feed
    Route::get('/activity', [ActivityController::class, 'index'])->name('activity.index');

    // Reports/Analytics
    Route::get('/reports', [ReportsController::class, 'index'])->name('reports.index');

    // Help & Documentation
    Route::get('/help', [HelpController::class, 'index'])->name('help.index');
    Route::get('/documentation', [DocumentationController::class, 'index'])->name('documentation.index');

    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');

    // Profile
    Route::get('/profile', [ProfileController::class, 'index'])->name('profile.index');
});


//user routes end

// Admin Routes are loaded from routes/admin.php via bootstrap/app.php
<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;                // ← make sure to import Route
use Spatie\Permission\Middleware\RoleMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',  // if you have channels.php
        health: '/up',
        then: function () {
            // register your admin routes here:
            Route::middleware(['web', 'auth', 'role:admin'])
                 ->prefix('admin')
                 ->name('admin.')
                 ->group(function () {
                     require base_path('routes/admin.php');
                 });
        }
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
        ]);
        
        $middleware->alias([
            'role'  => RoleMiddleware::class,
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
            'verified' => \App\Http\Middleware\EnsureEmailIsVerified::class,
            'permission' => \App\Http\Middleware\CheckPermission::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Handle 404 errors with marketing-styled page
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e, \Illuminate\Http\Request $request) {
            if (!$request->expectsJson() && !$request->is('admin/*') && !$request->is('dashboard/*') && !$request->is('api/*')) {
                return \Inertia\Inertia::render('Errors/NotFound', [
                    'meta' => [
                        'title' => '404 — BacklinkPro',
                        'description' => 'Page not found.',
                    ],
                ])->toResponse($request)->setStatusCode(404);
            }
        });

        // Handle 500 errors with marketing-styled page (production only)
        $exceptions->render(function (\Throwable $e, \Illuminate\Http\Request $request) {
            if (app()->environment('production') && !$request->expectsJson() && !$request->is('admin/*') && !$request->is('dashboard/*') && !$request->is('api/*')) {
                return \Inertia\Inertia::render('Errors/ServerError', [
                    'meta' => [
                        'title' => '500 — BacklinkPro',
                        'description' => 'Server error.',
                    ],
                ])->toResponse($request)->setStatusCode(500);
            }
        });
    })
    ->create();

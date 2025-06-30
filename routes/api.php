<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\UserCampaignController;

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
         Route::get('/user-campaign',     [UserCampaignController::class, 'index']);
    Route::get('/user-campaign/{id}', [UserCampaignController::class, 'show']);
});

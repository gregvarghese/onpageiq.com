<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group.
|
*/

Route::prefix('v1')->name('api.')->group(function () {
    // Public routes
    Route::post('/auth/token', [\App\Http\Controllers\Api\V1\AuthController::class, 'createToken']);

    // Protected routes
    Route::middleware('auth:sanctum')->group(function () {
        // Auth
        Route::delete('/auth/token', [\App\Http\Controllers\Api\V1\AuthController::class, 'revokeToken']);
        Route::get('/user', [\App\Http\Controllers\Api\V1\AuthController::class, 'user']);

        // Projects
        Route::apiResource('projects', \App\Http\Controllers\Api\V1\ProjectController::class);
        Route::get('/projects/{project}/urls', [\App\Http\Controllers\Api\V1\UrlController::class, 'index']);
        Route::post('/projects/{project}/urls', [\App\Http\Controllers\Api\V1\UrlController::class, 'store']);

        // URLs
        Route::get('/urls/{url}', [\App\Http\Controllers\Api\V1\UrlController::class, 'show']);
        Route::delete('/urls/{url}', [\App\Http\Controllers\Api\V1\UrlController::class, 'destroy']);

        // Scans
        Route::post('/urls/{url}/scan', [\App\Http\Controllers\Api\V1\ScanController::class, 'store']);
        Route::get('/scans/{scan}', [\App\Http\Controllers\Api\V1\ScanController::class, 'show']);
        Route::get('/scans/{scan}/issues', [\App\Http\Controllers\Api\V1\ScanController::class, 'issues']);

        // Credits
        Route::get('/credits/balance', [\App\Http\Controllers\Api\V1\CreditController::class, 'balance']);
        Route::get('/credits/transactions', [\App\Http\Controllers\Api\V1\CreditController::class, 'transactions']);

        // Webhooks
        Route::apiResource('webhooks', \App\Http\Controllers\Api\V1\WebhookController::class);
    });
});

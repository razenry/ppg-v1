<?php

use App\Http\Controllers\Api\Admin\NodeController;
use App\Http\Controllers\Api\Client\ServerController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::middleware(['auth:sanctum'])->group(function () {
    // Client endpoints
    Route::apiResource('servers', ServerController::class)->except(['update']);

    // Admin endpoints
    Route::middleware(['admin'])->prefix('admin')->name('api.admin.')->group(function () {
        Route::apiResource('nodes', NodeController::class);
    });
});

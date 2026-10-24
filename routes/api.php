<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductPriceController;

// API routes for products and product prices
Route::prefix('v1')->group(function () {
    Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:login');

    Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {
        Route::apiResource('products', ProductController::class);
        Route::apiResource('products.prices', ProductPriceController::class)->only(['index', 'store']);

        Route::post('auth/logout', [AuthController::class, 'logout']);
    });
});

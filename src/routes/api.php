<?php

use App\Http\Controllers\Api\AuthTokenController;
use App\Http\Controllers\Api\CategoryApiController;
use App\Http\Controllers\Api\ProductApiController;
use App\Http\Controllers\Api\PurchaseOrderApiController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/tokens', [AuthTokenController::class, 'store'])->middleware('throttle:5,1');

Route::middleware('auth:sanctum')->group(function () {
    Route::delete('/tokens/current', [AuthTokenController::class, 'destroy']);

    Route::get('/me', function (Request $request) {
        return response()->json($request->user());
    });

    Route::middleware('abilities:categories:read')->group(function () {
        Route::get('/categories', [CategoryApiController::class, 'index']);
        Route::get('/categories/{category}', [CategoryApiController::class, 'show']);
    });

    Route::middleware('abilities:categories:write')->group(function () {
        Route::post('/categories', [CategoryApiController::class, 'store']);
        Route::put('/categories/{category}', [CategoryApiController::class, 'update']);
        Route::delete('/categories/{category}', [CategoryApiController::class, 'destroy']);
    });

    Route::middleware('abilities:products:read')->group(function () {
        Route::get('/products', [ProductApiController::class, 'index']);
        Route::get('/products/{product}', [ProductApiController::class, 'show']);
    });

    Route::middleware('abilities:products:write')->group(function () {
        Route::post('/products', [ProductApiController::class, 'store']);
        Route::put('/products/{product}', [ProductApiController::class, 'update']);
        Route::delete('/products/{product}', [ProductApiController::class, 'destroy']);
    });

    Route::middleware('abilities:calendar:read')->group(function () {
        Route::get('/purchase-orders', [PurchaseOrderApiController::class, 'index']);
        Route::get('/purchase-orders/{purchaseOrder}', [PurchaseOrderApiController::class, 'show']);
    });

    Route::middleware('abilities:calendar:write')->group(function () {
        Route::post('/purchase-orders', [PurchaseOrderApiController::class, 'store']);
        Route::put('/purchase-orders/{purchaseOrder}', [PurchaseOrderApiController::class, 'update']);
        Route::delete('/purchase-orders/{purchaseOrder}', [PurchaseOrderApiController::class, 'destroy']);
    });
});
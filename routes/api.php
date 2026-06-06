<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\StockMovementController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {

    Route::get('/dashboard/summary', [ProductController::class, 'summary']);

    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/user', function (\Illuminate\Http\Request $request) {
        return $request->user();
    });

    Route::get('/products/low-stock', [ProductController::class, 'lowStock']);

    Route::get('/products/{product}/kardex', [ProductController::class, 'kardex']);

    Route::apiResource('products', ProductController::class);

    Route::post('/stock-adjustments', [StockMovementController::class, 'adjust']);

    Route::post('/stock-movements', [StockMovementController::class, 'store']);

    Route::get('/products/{id}/movements', [StockMovementController::class, 'productMovements']);

});


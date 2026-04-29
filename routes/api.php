<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\DebtController;
use App\Http\Controllers\Api\FarmerController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\RepaymentController;
use App\Http\Controllers\Api\TransactionController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes (Farmers Market Platform - Côte d'Ivoire)
|--------------------------------------------------------------------------
*/

// Public
Route::post('auth/login', [AuthController::class, 'login'])->middleware('throttle:10,1');

// Authenticated
Route::middleware(['auth:sanctum'])->group(function () {
    // Auth
    Route::get('auth/me', [AuthController::class, 'me']);
    Route::post('auth/logout', [AuthController::class, 'logout']);

    // Users management - admin or supervisor only
    Route::middleware('role:admin,supervisor')->group(function () {
        Route::apiResource('users', UserController::class);
    });

    // Categories - read for everyone, write for admin/supervisor
    Route::get('categories', [CategoryController::class, 'index']);
    Route::get('categories/{category}', [CategoryController::class, 'show']);
    Route::middleware('role:admin,supervisor')->group(function () {
        Route::post('categories', [CategoryController::class, 'store']);
        Route::match(['put', 'patch'], 'categories/{category}', [CategoryController::class, 'update']);
        Route::delete('categories/{category}', [CategoryController::class, 'destroy']);
    });

    // Products
    Route::get('products', [ProductController::class, 'index']);
    Route::get('products/{product}', [ProductController::class, 'show']);
    Route::middleware('role:admin,supervisor')->group(function () {
        Route::post('products', [ProductController::class, 'store']);
        Route::match(['put', 'patch'], 'products/{product}', [ProductController::class, 'update']);
        Route::delete('products/{product}', [ProductController::class, 'destroy']);
    });

    // Farmers - all authenticated can read & search; create/update by any role; delete admin/supervisor
    Route::get('farmers', [FarmerController::class, 'index']);
    Route::get('farmers/{farmer}', [FarmerController::class, 'show']);
    Route::post('farmers', [FarmerController::class, 'store']);
    Route::match(['put', 'patch'], 'farmers/{farmer}', [FarmerController::class, 'update']);
    Route::delete('farmers/{farmer}', [FarmerController::class, 'destroy'])->middleware('role:admin,supervisor');

    // Transactions (checkout)
    Route::get('transactions', [TransactionController::class, 'index']);
    Route::get('transactions/{transaction}', [TransactionController::class, 'show']);
    Route::post('transactions', [TransactionController::class, 'checkout']);

    // Debts (read-only)
    Route::get('debts', [DebtController::class, 'index']);
    Route::get('debts/{debt}', [DebtController::class, 'show']);

    // Repayments (FIFO)
    Route::get('repayments', [RepaymentController::class, 'index']);
    Route::get('repayments/{repayment}', [RepaymentController::class, 'show']);
    Route::post('repayments', [RepaymentController::class, 'store']);
});

Route::fallback(function () {
    return response()->json([
        'success' => false,
        'message' => 'Route not found.',
        'data' => null,
    ], 404);
});

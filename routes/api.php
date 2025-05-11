<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\TagController;
use App\Http\Controllers\Api\TranslationController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Auth Routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Protected Routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    
    // Translation Routes
    Route::apiResource('translations', TranslationController::class);
    
    // Additional Translation Endpoints
    Route::get('translations/search', [TranslationController::class, 'search']);
    Route::get('translations/export', [TranslationController::class, 'export']);
    
    // Tag Routes
    Route::apiResource('tags', TagController::class);
});



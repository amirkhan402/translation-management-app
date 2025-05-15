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
    
    // Additional Translation Endpoints (must come before resource routes)
    Route::get('translations/export', [TranslationController::class, 'export']);
    
    // Translation Routes
    Route::apiResource('translations', TranslationController::class);
    
    // Tag Routes
    Route::apiResource('tags', TagController::class);
});

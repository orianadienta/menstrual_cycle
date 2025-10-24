<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CycleProfileController;
use App\Http\Controllers\HealthProfileController;
use App\Http\Controllers\CycleController;
use App\Http\Controllers\FirstPredictionController;
use App\Http\Controllers\PredictionController;

Route::post('/register', [AuthController::class, 'register'])->name('register');
Route::post('/login', [AuthController::class, 'login'])->name('login');

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/get-user', [AuthController::class, 'userInfo'])->name('get-user');
    Route::post('/logout', [AuthController::class, 'logOut'])->name('logout');
});

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/cycle-profile', [CycleProfileController::class, 'setupCycleProfile']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/first-prediction', [FirstPredictionController::class, 'show']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/predictions', [PredictionController::class, 'index']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/health-profile', [HealthProfileController::class, 'setupHealthCondition']);
    Route::get('/health-profile', [HealthProfileController::class, 'index']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/cycles', [CycleController::class, 'index']);
    Route::post('/cycles', [CycleController::class, 'markPeriod']);
    Route::post('/cycles/{cycle}/update-day', [CycleController::class, 'updateMarkPeriod']);
});



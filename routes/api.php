<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CycleProfileController;
use App\Http\Controllers\HealthProfileController;
use App\Http\Controllers\CycleController;
use App\Http\Controllers\FirstPredictionController;
use App\Http\Controllers\PredictionController;
use App\Http\Controllers\SymptomLogController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RecommendationDisplayController;
use Illuminate\Http\Request;


Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login'])->name('login');


Route::middleware('auth:sanctum')->group(function () {

    Route::get('/get-user', [AuthController::class, 'userInfo'])->name('get-user');
    Route::post('/logout', [AuthController::class, 'logOut'])->name('logout');
    
   
    Route::post('/cycle-profile', [CycleProfileController::class, 'setupCycleProfile']);
    Route::get('/first-prediction', [FirstPredictionController::class, 'show']);
    

    Route::prefix('cycles')->group(function () {
        Route::get('/', [CycleController::class, 'index']); // List all cycles
        Route::post('/mark-period', [CycleController::class, 'markPeriod']); // Mark new period (auto-generate recommendations)
        Route::put('/{cycle}/update-mark', [CycleController::class, 'updateMarkPeriod']); // Update period day (auto-generate recommendations)
        Route::get('/history', [CycleController::class, 'getCycleHistory']); // Cycle history for history screen
    });
    

    Route::get('/predictions', [PredictionController::class, 'index']);
    

    Route::prefix('symptom-logs')->group(function () {
        Route::post('/', [SymptomLogController::class, 'storeLog']); // Store/update symptom log (auto-generate recommendations)
        Route::get('/show', [SymptomLogController::class, 'show']); // Get symptom by specific date
        Route::get('/history', [SymptomLogController::class, 'getSymptomHistory']); // Symptom history for history screen
    });
    
    Route::get('/health-conditions', [HealthProfileController::class, 'getHealthConditions']);

    Route::prefix('health-profile')->group(function () {
        Route::get('/', [HealthProfileController::class, 'index']); // Get current health profile
        Route::post('/setup', [HealthProfileController::class, 'setupHealthCondition']); // Setup/update health conditions (auto-generate recommendations)
    });
    
    Route::prefix('reports')->group(function () {
        Route::get('/cycle', [ReportController::class, 'getCycleReport']); // Cycle statistics report (FIGO standards)
    });

    Route::prefix('recommendations')->group(function () {
        Route::get('/', [RecommendationDisplayController::class, 'index']);
        Route::get('/recommendations', [RecommendationDisplayController::class, 'allRecommendations']);
        Route::post('/recommendation/refresh', [RecommendationDisplayController::class, 'refreshRecommendations']);
    });

});







    




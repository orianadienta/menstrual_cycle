<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CycleProfileController;
use App\Http\Controllers\HealthProfileController;
use App\Http\Controllers\CycleController;
use App\Http\Controllers\FirstPredictionController;
use App\Http\Controllers\PredictionController;
use App\Http\Controllers\SymptomLogController;
use App\Http\Controllers\SymptomController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RecommendationDisplayController;
use App\Http\Controllers\TrackingStatusController;
use App\Http\Controllers\NotificationController;
use Illuminate\Http\Request;

// ========== PUBLIC ROUTES (NO AUTH) ==========
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login'])->name('login');

Route::post('/auth/google/mobile', [AuthController::class, 'googleAuthMobile']);

// ========== PROTECTED ROUTES (WITH AUTH) ==========
Route::middleware('auth:sanctum')->group(function () {
    
    // Auth
    Route::get('/get-user', [AuthController::class, 'userInfo'])->name('get-user');
    Route::post('/logout', [AuthController::class, 'logOut'])->name('logout');
    
    // ========== CYCLE PROFILE ==========
    Route::prefix('cycle-profile')->group(function () {
        Route::post('/', [CycleProfileController::class, 'setupCycleProfile']);
        Route::get('/show', [CycleProfileController::class, 'show']); 
    });
    
    // ========== CYCLES ==========
    Route::prefix('cycles')->group(function () {
        Route::get('/', [CycleController::class, 'index']); // List all cycles
        Route::post('/mark-period', [CycleController::class, 'markPeriod']); 
        Route::delete('/{cycle}', [CycleController::class, 'destroy']);
        Route::put('/{cycle}/update-mark', [CycleController::class, 'updateMarkPeriod']); 
        Route::get('/history', [CycleController::class, 'getCycleHistory']); 
    });
    
    // ========== PREDICTIONS ==========
    Route::get('/first-prediction', [FirstPredictionController::class, 'show']);
    Route::get('/predictions', [PredictionController::class, 'index']);
    
    // ========== SYMPTOMS & SYMPTOM LOGS ==========
    Route::get('/symptoms', [SymptomController::class, 'index']);
    
    Route::prefix('symptom-logs')->group(function () {
        Route::post('/', [SymptomLogController::class, 'storeLog']); 
        Route::get('/show', [SymptomLogController::class, 'show']); 
        Route::get('/calendar', [SymptomLogController::class, 'getCalendarSymptoms']);
        Route::get('/history', [SymptomLogController::class, 'getSymptomHistory']); 
    });
    
    // ========== HEALTH CONDITIONS & PROFILE ==========
    Route::get('/health-conditions', [HealthProfileController::class, 'getHealthConditions']);
    
    Route::prefix('health-profile')->group(function () {
        Route::get('/', [HealthProfileController::class, 'index']);
        Route::post('/setup', [HealthProfileController::class, 'setupHealthCondition']); 
    });
    
    // ========== REPORTS ==========
    Route::prefix('reports')->group(function () {
        Route::get('/cycle', [ReportController::class, 'getCycleReport']); 
        Route::get('/dashboard', [ReportController::class, 'getDashboardReport']);
    });
    
    // ========== RECOMMENDATIONS ==========
    Route::prefix('recommendations')->group(function () {
        Route::get('/', [RecommendationDisplayController::class, 'index']);
        Route::get('/all', [RecommendationDisplayController::class, 'allRecommendations']);
        Route::post('/refresh', [RecommendationDisplayController::class, 'refreshRecommendations']);
    });
    
    // ========== TRACKING STATUS ==========
    Route::prefix('tracking-status')->group(function () {
        Route::get('/', [TrackingStatusController::class, 'getStatus']);
        Route::post('/pause', [TrackingStatusController::class, 'pause']);
        Route::post('/resume', [TrackingStatusController::class, 'resume']);
    });

    // ===== NOTIFICATION ENDPOINTS =====
    Route::prefix('notification')->group(function () {
        Route::post('/register-device', [NotificationController::class, 'registerDevice']);
        Route::delete('/unregister-device', [NotificationController::class, 'unregisterDevice']);
        Route::get('/devices', [NotificationController::class, 'getDevices']);
    });
    
});
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CycleProfileController;

Route::post('/register', [AuthController::class, 'register'])->name('register');
Route::post('/login', [AuthController::class, 'login'])->name('login');

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/get-user', [AuthController::class, 'userInfo'])->name('get-user');
    Route::post('/logout', [AuthController::class, 'logOut'])->name('logout');
});

// Route::post('/cycle-profile', [CycleProfileController::class, 'setup']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/cycle-profile', [CycleProfileController::class, 'setup']);
});

// Route::post('/test', function () {
//     return response()->json(['ok' => true]);
// });

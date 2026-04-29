<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DataStudentController;
use App\Http\Controllers\Api\PembayaranKasController;
use App\Http\Controllers\Api\PengeluaranKasController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


// Routes accessible by both Admin and Student
Route::middleware(['auth:sanctum', 'role:admin,student'])->group(function () {
    Route::apiResource('/pembayaran', PembayaranKasController::class);
    Route::apiResource('/pengeluaran', PengeluaranKasController::class);
    Route::apiResource('/student', DataStudentController::class);
    Route::post('/logout', [AuthController::class, 'logout']);
});

// Routes accessible only by Admin
Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
    Route::apiResource('/pembayaran', PembayaranKasController::class)->except(['index', 'show']);
    Route::apiResource('/pengeluaran', PengeluaranKasController::class)->except(['index', 'show']);
    Route::apiResource('/student', DataStudentController::class)->except(['index', 'show']);
    Route::post('/logout', [AuthController::class, 'logout']);
});

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login-Admin', [AuthController::class, 'loginAdmin']);
Route::post('/login-Student', [AuthController::class, 'loginStudent']);

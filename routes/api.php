<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AttendanceController;





Route::post('/login', [AuthController::class, 'login']);

Route::middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/attendance', [AttendanceController::class, 'store']);
    Route::get('/attendance/{id}', [AttendanceController::class, 'getAttendanceById']);
    Route::get('/students/{student_id}/attendance', [AttendanceController::class, 'getAttendanceForStudent']);
    Route::put('/attendance/{id}', [AttendanceController::class, 'updateAttendance']);
    Route::delete('/attendance/{id}', [AttendanceController::class, 'deleteAttendance']);
    Route::post('/attendance/bulk', [AttendanceController::class, 'bulkMarkAttendance']);
});

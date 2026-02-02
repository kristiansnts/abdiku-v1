<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\Attendance\AttendanceHistoryController;
use App\Http\Controllers\Api\V1\Attendance\AttendanceRequestController;
use App\Http\Controllers\Api\V1\Attendance\AttendanceStatusController;
use App\Http\Controllers\Api\V1\Attendance\ClockInController;
use App\Http\Controllers\Api\V1\Attendance\ClockOutController;
use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\Company\CompanyLocationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes - V1
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {

    // Authentication (public)
    Route::prefix('auth')->group(function () {
        Route::post('login', [AuthController::class, 'login'])
            ->middleware('throttle:5,1');
    });

    // Authenticated routes
    Route::middleware(['auth:sanctum'])->group(function () {

        // Auth
        Route::prefix('auth')->group(function () {
            Route::post('logout', [AuthController::class, 'logout']);
            Route::post('logout-all', [AuthController::class, 'logoutAllDevices']);
            Route::get('me', [AuthController::class, 'me']);
            Route::get('devices', [AuthController::class, 'devices']);
        });

        // Attendance (requires employee)
        Route::middleware(['employee.required'])->group(function () {

            Route::prefix('attendance')->middleware('throttle:60,1')->group(function () {
                Route::post('clock-in', ClockInController::class);
                Route::post('clock-out', ClockOutController::class);
                Route::get('status', AttendanceStatusController::class);
                Route::get('history', AttendanceHistoryController::class);

                // Attendance correction requests
                Route::get('requests', [AttendanceRequestController::class, 'index']);
                Route::post('requests', [AttendanceRequestController::class, 'store']);
                Route::get('requests/{id}', [AttendanceRequestController::class, 'show']);
                Route::delete('requests/{id}', [AttendanceRequestController::class, 'cancel']);
            });

            Route::prefix('company')->group(function () {
                Route::get('locations', CompanyLocationController::class);
            });
        });
    });
});

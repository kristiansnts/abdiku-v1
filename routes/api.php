<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\ActivityController;
use App\Http\Controllers\Api\V1\Attendance\AttendanceDetailController;
use App\Http\Controllers\Api\V1\Attendance\AttendanceHistoryController;
use App\Http\Controllers\Api\V1\Attendance\AttendanceRequestController;
use App\Http\Controllers\Api\V1\Attendance\AttendanceStatusController;
use App\Http\Controllers\Api\V1\Attendance\ClockInController;
use App\Http\Controllers\Api\V1\Attendance\ClockOutController;
use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\Company\CompanyLocationController;
use App\Http\Controllers\Api\V1\Employee\EmployeeDetailController;
use App\Http\Controllers\Api\V1\Employee\EmployeePayslipController;
use App\Http\Controllers\Api\V1\Employee\EmployeeSalaryController;
use App\Http\Controllers\Api\V1\Employee\PayslipDownloadController;
use App\Http\Controllers\Api\V1\Employee\PayslipDownloadUrlController;
use App\Http\Controllers\Api\V1\HomeController;
use App\Http\Controllers\Api\V1\Notification\FcmTokenController;
use App\Http\Controllers\Api\V1\Notification\NotificationController;
use App\Http\Controllers\Api\V1\PayslipController;
use App\Http\Controllers\Api\V1\PayslipSignedDownloadController;
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

    // Public signed download (no auth required, signature validated)
    Route::get('payslips/{id}/download/{employee_id}', PayslipSignedDownloadController::class)
        ->name('payslip.download.signed')
        ->middleware('signed');

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

                // Attendance detail (must be after named routes)
                Route::get('{id}', AttendanceDetailController::class)->where('id', '[0-9]+');
            });

            // Activity feed
            Route::get('activities', ActivityController::class);

            // Home aggregator
            Route::get('home', HomeController::class);

            // Payslips alias
            Route::get('payslips', [PayslipController::class, 'index']);
            Route::get('payslips/{id}', [PayslipController::class, 'show']);
            Route::get('payslips/{id}/download', PayslipDownloadController::class);
            Route::get('payslips/{id}/download-url', PayslipDownloadUrlController::class);

            Route::prefix('company')->group(function () {
                Route::get('locations', CompanyLocationController::class);
            });

            Route::prefix('employee')->group(function () {
                Route::get('detail', EmployeeDetailController::class);
                Route::get('salary', EmployeeSalaryController::class);
                Route::get('payslips', [EmployeePayslipController::class, 'index']);
                Route::get('payslips/{id}', [EmployeePayslipController::class, 'show']);
                Route::get('payslips/{id}/download', PayslipDownloadController::class);
                Route::get('payslips/{id}/download-url', PayslipDownloadUrlController::class);
            });
        });

        // Notifications (all authenticated users)
        Route::prefix('notifications')->group(function () {
            Route::get('/', [NotificationController::class, 'index']);
            Route::get('/unread-count', [NotificationController::class, 'unreadCount']);
            Route::post('/{id}/read', [NotificationController::class, 'markAsRead']);
            Route::post('/read-all', [NotificationController::class, 'markAllAsRead']);

            Route::post('/fcm-token', [FcmTokenController::class, 'update']);
            Route::delete('/fcm-token', [FcmTokenController::class, 'destroy']);
        });
    });
});

<?php

use App\Exceptions\Api\AttendanceException;
use App\Exceptions\Api\DeviceException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'employee.required' => \App\Http\Middleware\Api\EnsureEmployeeExists::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Handle authentication errors for API routes
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'UNAUTHENTICATED',
                        'message' => 'Token tidak valid atau sudah kedaluwarsa.',
                    ],
                ], 401);
            }
        });

        $exceptions->render(function (AttendanceException $e, Request $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => $e->errorCode,
                        'message' => $e->getMessage(),
                    ],
                ], $e->statusCode);
            }
        });

        $exceptions->render(function (DeviceException $e, Request $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => $e->errorCode,
                        'message' => $e->getMessage(),
                        'data' => $e->data,
                    ],
                ], $e->statusCode);
            }
        });
    })->create();

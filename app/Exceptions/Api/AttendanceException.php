<?php

declare(strict_types=1);

namespace App\Exceptions\Api;

use Exception;
use Illuminate\Http\JsonResponse;

class AttendanceException extends Exception
{
    public string $errorCode;
    public int $statusCode;

    public function __construct(string $message, string $errorCode, int $statusCode = 422)
    {
        // Pass message to parent Exception
        parent::__construct($message);
        
        $this->errorCode = (string) $errorCode;
        $this->statusCode = (int) $statusCode;
    }

    public function render($request): JsonResponse
    {
        return response()->json([
            'success' => false,
            'error' => [
                'code' => $this->errorCode,
                'message' => $this->getMessage(),
            ],
        ], $this->statusCode);
    }

    public static function alreadyClockedIn(): self
    {
        return new self('Anda sudah melakukan clock in hari ini.', 'ALREADY_CLOCKED_IN', 422);
    }

    public static function notClockedIn(): self
    {
        return new self('Anda belum melakukan clock in hari ini.', 'NOT_CLOCKED_IN', 422);
    }

    public static function alreadyClockedOut(): self
    {
        return new self('Anda sudah melakukan clock out hari ini.', 'ALREADY_CLOCKED_OUT', 422);
    }

    public static function attendanceLocked(): self
    {
        return new self('Data kehadiran sudah terkunci dan tidak dapat diubah.', 'ATTENDANCE_LOCKED', 403);
    }

    public static function invalidCredentials(): self
    {
        return new self('Email atau password salah.', 'INVALID_CREDENTIALS', 401);
    }
}

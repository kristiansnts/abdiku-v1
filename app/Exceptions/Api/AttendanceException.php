<?php

declare(strict_types=1);

namespace App\Exceptions\Api;

use Exception;

class AttendanceException extends Exception
{
    public function __construct(
        string $message,
        public readonly string $errorCode,
        public readonly int $statusCode = 422,
    ) {
        parent::__construct($message);
    }

    public static function alreadyClockedIn(): self
    {
        return new self(
            message: 'Anda sudah melakukan clock in hari ini.',
            errorCode: 'ALREADY_CLOCKED_IN',
            statusCode: 422,
        );
    }

    public static function notClockedIn(): self
    {
        return new self(
            message: 'Anda belum melakukan clock in hari ini.',
            errorCode: 'NOT_CLOCKED_IN',
            statusCode: 422,
        );
    }

    public static function alreadyClockedOut(): self
    {
        return new self(
            message: 'Anda sudah melakukan clock out hari ini.',
            errorCode: 'ALREADY_CLOCKED_OUT',
            statusCode: 422,
        );
    }

    public static function attendanceLocked(): self
    {
        return new self(
            message: 'Data kehadiran sudah terkunci dan tidak dapat diubah.',
            errorCode: 'ATTENDANCE_LOCKED',
            statusCode: 403,
        );
    }

    public static function invalidCredentials(): self
    {
        return new self(
            message: 'Email atau password salah.',
            errorCode: 'INVALID_CREDENTIALS',
            statusCode: 401,
        );
    }
}

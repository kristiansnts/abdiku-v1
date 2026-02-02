<?php

declare(strict_types=1);

namespace App\Exceptions\Api;

use Exception;

class DeviceException extends Exception
{
    public function __construct(
        string $message,
        public readonly string $errorCode,
        public readonly int $statusCode = 403,
        public readonly ?array $data = null,
    ) {
        parent::__construct($message);
    }

    public static function deviceBlocked(string $reason): self
    {
        return new self(
            message: 'Perangkat Anda telah diblokir. Alasan: '.$reason,
            errorCode: 'DEVICE_BLOCKED',
            statusCode: 403,
        );
    }

    public static function differentDeviceActive(string $activeDeviceName): self
    {
        return new self(
            message: 'Akun Anda sudah aktif di perangkat lain ('.$activeDeviceName.'). Silakan logout dari perangkat tersebut atau hubungi HR.',
            errorCode: 'DIFFERENT_DEVICE_ACTIVE',
            statusCode: 403,
            data: ['active_device' => $activeDeviceName],
        );
    }

    public static function deviceNotRegistered(): self
    {
        return new self(
            message: 'Perangkat belum terdaftar.',
            errorCode: 'DEVICE_NOT_REGISTERED',
            statusCode: 403,
        );
    }
}

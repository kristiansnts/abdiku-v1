<?php

declare(strict_types=1);

namespace App\Exceptions\Api;

use Exception;
use Illuminate\Http\JsonResponse;

class DeviceException extends Exception
{
    public string $errorCode;
    public int $statusCode;
    public ?array $data;

    public function __construct(
        string $message,
        string $errorCode,
        int $statusCode = 403,
        ?array $data = null
    ) {
        parent::__construct($message, $statusCode);
        $this->errorCode = $errorCode;
        $this->statusCode = $statusCode;
        $this->data = $data;
    }

    /**
     * Render the exception into an HTTP response.
     */
    public function render($request): JsonResponse
    {
        return response()->json([
            'success' => false,
            'error' => [
                'code' => $this->errorCode,
                'message' => $this->getMessage(),
                'data' => $this->data,
            ],
        ], $this->statusCode);
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

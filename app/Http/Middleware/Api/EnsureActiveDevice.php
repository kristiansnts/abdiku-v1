<?php

declare(strict_types=1);

namespace App\Http\Middleware\Api;

use App\Domain\Auth\Services\DeviceManagementService;
use App\Exceptions\Api\DeviceException;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveDevice
{
    public function __construct(
        private readonly DeviceManagementService $deviceService,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        // Only enforce for write actions
        if (! in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            return $next($request);
        }

        $user = $request->user();
        if (! $user) {
            return $next($request);
        }

        $deviceId = $request->header('X-Device-Id')
            ?? $request->input('device_id')
            ?? data_get($request->input('evidence'), 'device.device_id');

        if (! $deviceId) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'DEVICE_ID_REQUIRED',
                    'message' => 'ID perangkat wajib diisi.',
                ],
            ], 422);
        }

        try {
            $this->deviceService->validateDevice($user, $deviceId);
        } catch (DeviceException $e) {
            return $e->render($request);
        }

        return $next($request);
    }
}

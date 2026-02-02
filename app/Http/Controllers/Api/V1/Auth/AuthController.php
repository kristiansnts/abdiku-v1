<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Auth;

use App\Domain\Auth\Services\DeviceManagementService;
use App\Exceptions\Api\AttendanceException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\LoginRequest;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function __construct(
        private readonly DeviceManagementService $deviceService,
    ) {}

    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw AttendanceException::invalidCredentials();
        }

        // Register/validate device
        $device = $this->deviceService->registerDeviceForLogin(
            user: $user,
            deviceId: $request->device_id,
            deviceName: $request->device_name,
            deviceModel: $request->device_model,
            deviceOs: $request->device_os,
            appVersion: $request->app_version,
            ipAddress: $request->ip(),
            forceSwitch: $request->boolean('force_switch', false),
        );

        // Create token with device info
        $token = $user->createToken(
            name: $request->device_name,
            abilities: ['*'],
        )->plainTextToken;

        return response()->json([
            'success' => true,
            'data' => [
                'token' => $token,
                'user' => new UserResource($user->load('company', 'employee')),
                'device' => [
                    'id' => $device->id,
                    'device_id' => $device->device_id,
                    'device_name' => $device->device_name,
                    'is_active' => $device->is_active,
                ],
            ],
            'message' => 'Login berhasil.',
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logout berhasil.',
        ]);
    }

    public function logoutAllDevices(Request $request): JsonResponse
    {
        $this->deviceService->logoutAllDevices($request->user());

        return response()->json([
            'success' => true,
            'message' => 'Logout dari semua perangkat berhasil.',
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'success' => true,
            'data' => [
                'user' => new UserResource($user->load('company', 'employee')),
                'active_device' => $user->activeDevice ? [
                    'id' => $user->activeDevice->id,
                    'device_id' => $user->activeDevice->device_id,
                    'device_name' => $user->activeDevice->device_name,
                    'last_login_at' => $user->activeDevice->last_login_at?->format('Y-m-d H:i:s'),
                ] : null,
            ],
        ]);
    }

    public function devices(Request $request): JsonResponse
    {
        $devices = $this->deviceService->getUserDevices($request->user());

        return response()->json([
            'success' => true,
            'data' => $devices->map(fn ($device) => [
                'id' => $device->id,
                'device_id' => $device->device_id,
                'device_name' => $device->device_name,
                'device_model' => $device->device_model,
                'device_os' => $device->device_os,
                'is_active' => $device->is_active,
                'is_blocked' => $device->is_blocked,
                'block_reason' => $device->block_reason,
                'last_login_at' => $device->last_login_at?->format('Y-m-d H:i:s'),
            ]),
        ]);
    }
}

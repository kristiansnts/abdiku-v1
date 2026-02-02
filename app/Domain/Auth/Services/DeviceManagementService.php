<?php

declare(strict_types=1);

namespace App\Domain\Auth\Services;

use App\Exceptions\Api\DeviceException;
use App\Models\User;
use App\Models\UserDevice;

class DeviceManagementService
{
    /**
     * Register or update device for user login.
     * Enforces one-user-one-device policy.
     *
     * @throws DeviceException
     */
    public function registerDeviceForLogin(
        User $user,
        string $deviceId,
        string $deviceName,
        ?string $deviceModel,
        ?string $deviceOs,
        ?string $appVersion,
        string $ipAddress,
        bool $forceSwitch = false,
    ): UserDevice {
        // Check if this device exists for this user
        $device = UserDevice::where('user_id', $user->id)
            ->where('device_id', $deviceId)
            ->first();

        if ($device) {
            // Device exists, check if blocked
            if ($device->isBlocked()) {
                throw DeviceException::deviceBlocked($device->block_reason ?? 'Tidak ada alasan');
            }

            // Update device info and activate
            $device->update([
                'device_name' => $deviceName,
                'device_model' => $deviceModel,
                'device_os' => $deviceOs,
                'app_version' => $appVersion,
            ]);

            $device->activate();
            $device->recordLogin($ipAddress);

            return $device;
        }

        // Check if user has another active device
        $activeDevice = UserDevice::where('user_id', $user->id)
            ->where('is_active', true)
            ->where('is_blocked', false)
            ->first();

        if ($activeDevice && ! $forceSwitch) {
            // User has another active device
            throw DeviceException::differentDeviceActive(
                $activeDevice->device_name ?? $activeDevice->device_model ?? 'Unknown Device'
            );
        }

        // Create new device and activate (deactivates others)
        $device = UserDevice::create([
            'user_id' => $user->id,
            'device_id' => $deviceId,
            'device_name' => $deviceName,
            'device_model' => $deviceModel,
            'device_os' => $deviceOs,
            'app_version' => $appVersion,
            'is_active' => false, // Will be activated below
            'is_blocked' => false,
        ]);

        $device->activate();
        $device->recordLogin($ipAddress);

        // Revoke all existing tokens when switching to new device
        $user->tokens()->delete();

        return $device;
    }

    /**
     * Validate that the device is still valid for the user.
     *
     * @throws DeviceException
     */
    public function validateDevice(User $user, string $deviceId): UserDevice
    {
        $device = UserDevice::where('user_id', $user->id)
            ->where('device_id', $deviceId)
            ->first();

        if (! $device) {
            throw DeviceException::deviceNotRegistered();
        }

        if ($device->isBlocked()) {
            throw DeviceException::deviceBlocked($device->block_reason ?? 'Tidak ada alasan');
        }

        if (! $device->is_active) {
            throw DeviceException::differentDeviceActive('another device');
        }

        return $device;
    }

    /**
     * Block a device.
     */
    public function blockDevice(UserDevice $device, User $blockedBy, string $reason): void
    {
        $device->block($blockedBy, $reason);
    }

    /**
     * Unblock a device.
     */
    public function unblockDevice(UserDevice $device): void
    {
        $device->unblock();
    }

    /**
     * Force logout user from all devices.
     */
    public function logoutAllDevices(User $user): void
    {
        // Revoke all tokens
        $user->tokens()->delete();

        // Deactivate all devices
        UserDevice::where('user_id', $user->id)
            ->update(['is_active' => false]);
    }

    /**
     * Get user's devices.
     */
    public function getUserDevices(User $user)
    {
        return UserDevice::where('user_id', $user->id)
            ->orderBy('last_login_at', 'desc')
            ->get();
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Notification;

use App\Http\Controllers\Controller;
use App\Models\UserDevice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class FcmTokenController extends Controller
{
    /**
     * Register or update FCM token for the current device
     */
    public function update(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'device_id' => 'required|string',
            'fcm_token' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $device = UserDevice::where('user_id', $request->user()->id)
            ->where('device_id', $request->device_id)
            ->first();

        if (!$device) {
            return response()->json([
                'success' => false,
                'message' => 'Device not found',
            ], 404);
        }

        $device->updateFcmToken($request->fcm_token);

        return response()->json([
            'success' => true,
            'message' => 'FCM token updated successfully',
            'data' => [
                'device_id' => $device->device_id,
                'fcm_token_updated_at' => $device->fcm_token_updated_at?->toIso8601String(),
            ],
        ]);
    }

    /**
     * Remove FCM token (on logout or permission revoked)
     */
    public function destroy(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'device_id' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $device = UserDevice::where('user_id', $request->user()->id)
            ->where('device_id', $request->device_id)
            ->first();

        if ($device) {
            $device->clearFcmToken();
        }

        return response()->json([
            'success' => true,
            'message' => 'FCM token removed successfully',
        ]);
    }
}

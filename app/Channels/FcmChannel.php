<?php

declare(strict_types=1);

namespace App\Channels;

use App\Models\User;
use App\Models\UserDevice;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class FcmChannel
{
    /**
     * Send the given notification using Expo's Push Notification service
     */
    public function send($notifiable, Notification $notification): void
    {
        if (!method_exists($notification, 'toFcm')) {
            return;
        }

        // Get FCM message data
        $fcmData = $notification->toFcm($notifiable);

        if (!$fcmData) {
            return;
        }

        // Get all active devices with FCM tokens for this user
        $devices = UserDevice::where('user_id', $notifiable->id)
            ->where('is_active', true)
            ->where('is_blocked', false)
            ->whereNotNull('fcm_token')
            ->get();

        if ($devices->isEmpty()) {
            Log::info('No active devices with FCM tokens found', [
                'user_id' => $notifiable->id,
                'notification' => get_class($notification),
            ]);
            return;
        }

        // Prepare messages for Expo Push API
        $messages = [];
        foreach ($devices as $device) {
            $messages[] = [
                'to' => $device->fcm_token,
                'sound' => 'default',
                'title' => $fcmData['title'] ?? 'Notification',
                'body' => $fcmData['body'] ?? '',
                'data' => $fcmData,
                'priority' => 'high',
                'channelId' => 'default',
            ];
        }

        // Send to Expo Push Notification service
        try {
            $response = Http::post('https://exp.host/--/api/v2/push/send', $messages);

            if ($response->successful()) {
                $results = $response->json('data', []);

                foreach ($results as $index => $result) {
                    $device = $devices[$index] ?? null;
                    if (!$device) continue;

                    if (isset($result['status']) && $result['status'] === 'ok') {
                        Log::info('Expo push notification sent', [
                            'user_id' => $notifiable->id,
                            'device_id' => $device->device_id,
                            'notification' => get_class($notification),
                        ]);
                    } else {
                        $error = $result['message'] ?? 'Unknown error';

                        // Clear invalid tokens
                        if (isset($result['details']['error']) &&
                            in_array($result['details']['error'], ['DeviceNotRegistered', 'InvalidCredentials'])) {
                            Log::warning('Expo token invalid, clearing', [
                                'user_id' => $notifiable->id,
                                'device_id' => $device->device_id,
                                'error' => $error,
                            ]);
                            $device->clearFcmToken();
                        } else {
                            Log::error('Expo push notification failed', [
                                'user_id' => $notifiable->id,
                                'device_id' => $device->device_id,
                                'error' => $error,
                            ]);
                        }
                    }
                }
            } else {
                Log::error('Expo Push API request failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to send Expo push notification', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}

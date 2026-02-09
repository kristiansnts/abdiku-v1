<?php

declare(strict_types=1);

namespace App\Notifications\Concerns;

trait HasFcmSupport
{
    /**
     * Convert database notification data to FCM format
     */
    public function toFcm($notifiable): array
    {
        $dbData = $this->toDatabase($notifiable);

        return [
            'notification_id' => (string) $this->id,
            'type' => $this->getFcmType(),
            'title' => $dbData['title'] ?? 'Notifikasi',
            'body' => $dbData['body'] ?? '',
            'icon' => $dbData['icon'] ?? 'heroicon-o-bell',
            'icon_color' => $dbData['iconColor'] ?? 'info',
            'related_id' => $this->getRelatedId(),
            'related_type' => $this->getRelatedType(),
            'created_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Get notification type for FCM routing
     */
    protected function getFcmType(): string
    {
        return 'general';
    }

    /**
     * Get related entity ID for deep linking
     */
    protected function getRelatedId(): ?string
    {
        return null;
    }

    /**
     * Get related entity type for deep linking
     */
    protected function getRelatedType(): ?string
    {
        return null;
    }
}

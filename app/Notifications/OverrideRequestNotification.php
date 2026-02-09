<?php

namespace App\Notifications;

use App\Channels\FcmChannel;
use App\Domain\Payroll\Models\OverrideRequest;
use App\Helpers\FilamentUrlHelper;
use App\Models\User;
use App\Notifications\Concerns\HasFcmSupport;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class OverrideRequestNotification extends Notification
{
    use Queueable, HasFcmSupport;

    public function __construct(
        public OverrideRequest $overrideRequest,
        public User $requestedBy
    ) {
    }

    public function via($notifiable): array
    {
        $channels = ['database'];

        // Send FCM to employee role users (mobile app users)
        if ($notifiable->hasRole('employee') || $notifiable->hasRole('hr') || $notifiable->hasRole('owner')) {
            $channels[] = FcmChannel::class;
        }

        return $channels;
    }

    public function toDatabase($notifiable): array
    {
        $employeeName = $this->overrideRequest->attendanceDecision->employee->name;
        $requesterName = $this->requestedBy->name;

        return [
            'format' => 'filament',
            'title' => 'Persetujuan Override Diperlukan',
            'body' => "HR ({$requesterName}) meminta persetujuan override untuk {$employeeName}",
            'icon' => 'heroicon-o-exclamation-triangle',
            'iconColor' => 'warning',
            'duration' => 'persistent',
            'color' => null,
            'status' => 'warning',
            'view' => 'filament-notifications::notification',
            'viewData' => [],
            'actions' => [
                [
                    'name' => 'view',
                    'color' => 'primary',
                    'event' => null,
                    'eventData' => [],
                    'dispatchDirection' => false,
                    'dispatchToComponent' => null,
                    'extraAttributes' => [],
                    'icon' => null,
                    'iconPosition' => 'before',
                    'iconSize' => null,
                    'isOutlined' => false,
                    'isDisabled' => false,
                    'label' => 'Tinjau',
                    'shouldClose' => false,
                    'shouldMarkAsRead' => true,
                    'shouldMarkAsUnread' => false,
                    'shouldOpenUrlInNewTab' => false,
                    'size' => 'sm',
                    'tooltip' => null,
                    'url' => FilamentUrlHelper::overrideRequestUrl($this->overrideRequest),
                    'view' => 'filament-notifications::actions.button-action',
                ],
                [
                    'name' => 'dismiss',
                    'color' => 'gray',
                    'event' => null,
                    'eventData' => [],
                    'dispatchDirection' => false,
                    'dispatchToComponent' => null,
                    'extraAttributes' => [],
                    'icon' => null,
                    'iconPosition' => 'before',
                    'iconSize' => null,
                    'isOutlined' => false,
                    'isDisabled' => false,
                    'label' => 'Nanti',
                    'shouldClose' => false,
                    'shouldMarkAsRead' => true,
                    'shouldMarkAsUnread' => false,
                    'shouldOpenUrlInNewTab' => false,
                    'size' => 'sm',
                    'tooltip' => null,
                    'url' => null,
                    'view' => 'filament-notifications::actions.link-action',
                ],
            ],
        ];
    }

    public function toArray($notifiable): array
    {
        return $this->toDatabase($notifiable);
    }

    protected function getFcmType(): string
    {
        return 'override_request';
    }

    protected function getRelatedId(): ?string
    {
        return (string) $this->overrideRequest->id;
    }

    protected function getRelatedType(): ?string
    {
        return 'override_request';
    }
}

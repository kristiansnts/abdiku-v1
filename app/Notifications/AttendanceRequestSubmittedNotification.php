<?php

namespace App\Notifications;

use App\Channels\FcmChannel;
use App\Domain\Attendance\Models\AttendanceRequest;
use App\Helpers\FilamentUrlHelper;
use App\Notifications\Concerns\HasFcmSupport;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class AttendanceRequestSubmittedNotification extends Notification
{
    use Queueable, HasFcmSupport;

    public function __construct(
        public AttendanceRequest $attendanceRequest
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
        $request = $this->attendanceRequest;
        $date = $request->attendanceRaw?->date ?? $request->requested_clock_in_at?->format('Y-m-d') ?? 'N/A';

        return [
            'format' => 'filament',
            'title' => 'Pengajuan Kehadiran Baru',
            'body' => "Pengajuan kehadiran baru dari {$request->employee->name} untuk tanggal {$date}",
            'icon' => 'heroicon-o-document-text',
            'iconColor' => 'info',
            'duration' => 'persistent',
            'color' => null,
            'status' => null,
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
                    'label' => 'Lihat',
                    'shouldClose' => false,
                    'shouldMarkAsRead' => true,
                    'shouldMarkAsUnread' => false,
                    'shouldOpenUrlInNewTab' => false,
                    'size' => 'sm',
                    'tooltip' => null,
                    'url' => FilamentUrlHelper::attendanceRequestUrl($request),
                    'view' => 'filament-notifications::actions.button-action',
                ],
                [
                    'name' => 'markAsRead',
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
                    'label' => 'Tandai Sudah Dibaca',
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
        return 'attendance_request_submitted';
    }

    protected function getRelatedId(): ?string
    {
        return (string) $this->attendanceRequest->id;
    }

    protected function getRelatedType(): ?string
    {
        return 'attendance_request';
    }
}

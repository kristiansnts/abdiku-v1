<?php

namespace App\Notifications;

use App\Domain\Attendance\Models\AttendanceRequest;
use App\Helpers\FilamentUrlHelper;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class AttendanceRequestReviewedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public AttendanceRequest $attendanceRequest,
        public bool $approved,
        public User $reviewer
    ) {
    }

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        $request = $this->attendanceRequest;
        $date = $request->attendanceRaw?->date ?? $request->requested_clock_in_at?->format('Y-m-d') ?? 'N/A';
        $reviewerName = $this->reviewer->name;

        return [
            'format' => 'filament',
            'title' => $this->approved ? 'Pengajuan Kehadiran Disetujui' : 'Pengajuan Kehadiran Ditolak',
            'body' => $this->approved
                ? "Pengajuan kehadiran Anda untuk tanggal {$date} telah disetujui oleh {$reviewerName}."
                : "Pengajuan kehadiran Anda untuk tanggal {$date} ditolak oleh {$reviewerName}.",
            'icon' => $this->approved ? 'heroicon-o-check-circle' : 'heroicon-o-x-circle',
            'iconColor' => $this->approved ? 'success' : 'danger',
            'duration' => 'persistent',
            'color' => null,
            'status' => $this->approved ? 'success' : 'warning',
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
                    'label' => 'Lihat Detail',
                    'shouldClose' => false,
                    'shouldMarkAsRead' => true,
                    'shouldMarkAsUnread' => false,
                    'shouldOpenUrlInNewTab' => false,
                    'size' => 'sm',
                    'tooltip' => null,
                    'url' => FilamentUrlHelper::attendanceRequestUrl($request),
                    'view' => 'filament-notifications::actions.button-action',
                ],
            ],
        ];
    }

    public function toArray($notifiable): array
    {
        return $this->toDatabase($notifiable);
    }
}

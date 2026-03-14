<?php

namespace App\Notifications;

use App\Channels\FcmChannel;
use App\Domain\Attendance\Models\AttendanceRaw;
use App\Helpers\FilamentUrlHelper;
use App\Models\User;
use App\Notifications\Concerns\HasFcmSupport;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class AttendanceRecordReviewedNotification extends Notification
{
    use Queueable, HasFcmSupport;

    public function __construct(
        public AttendanceRaw $attendanceRecord,
        public bool $approved,
        public User $reviewer
    ) {
    }

    public function via($notifiable): array
    {
        $channels = ['database'];

        if ($notifiable->hasRole('employee') || $notifiable->hasRole('hr') || $notifiable->hasRole('owner')) {
            $channels[] = FcmChannel::class;
        }

        return $channels;
    }

    public function toDatabase($notifiable): array
    {
        $date = $this->attendanceRecord->date
            ? $this->attendanceRecord->date->format('d M Y')
            : 'N/A';
        $reviewerName = $this->reviewer->name;

        return [
            'format'   => 'filament',
            'title'    => $this->approved ? 'Kehadiran Disetujui' : 'Kehadiran Ditolak',
            'body'     => $this->approved
                ? "Kehadiran Anda pada {$date} telah disetujui oleh {$reviewerName}."
                : "Kehadiran Anda pada {$date} ditolak oleh {$reviewerName}.",
            'icon'      => $this->approved ? 'heroicon-o-check-circle' : 'heroicon-o-x-circle',
            'iconColor' => $this->approved ? 'success' : 'danger',
            'duration'  => 'persistent',
            'color'     => null,
            'status'    => $this->approved ? 'success' : 'warning',
            'view'      => 'filament-notifications::notification',
            'viewData'  => [],
            'actions'   => [
                [
                    'name'                  => 'view',
                    'color'                 => 'primary',
                    'event'                 => null,
                    'eventData'             => [],
                    'dispatchDirection'     => false,
                    'dispatchToComponent'   => null,
                    'extraAttributes'       => [],
                    'icon'                  => null,
                    'iconPosition'          => 'before',
                    'iconSize'              => null,
                    'isOutlined'            => false,
                    'isDisabled'            => false,
                    'label'                 => 'Lihat Detail',
                    'shouldClose'           => false,
                    'shouldMarkAsRead'      => true,
                    'shouldMarkAsUnread'    => false,
                    'shouldOpenUrlInNewTab' => false,
                    'size'                  => 'sm',
                    'tooltip'               => null,
                    'url'                   => FilamentUrlHelper::attendanceRecordUrl($this->attendanceRecord),
                    'view'                  => 'filament-notifications::actions.button-action',
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
        return 'attendance_record_reviewed';
    }

    protected function getRelatedId(): ?string
    {
        return (string) $this->attendanceRecord->id;
    }

    protected function getRelatedType(): ?string
    {
        return 'attendance_record';
    }
}

<?php

namespace App\Notifications;

use App\Channels\FcmChannel;
use App\Domain\Leave\Models\LeaveRequest;
use App\Helpers\FilamentUrlHelper;
use App\Models\User;
use App\Notifications\Concerns\HasFcmSupport;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class LeaveRequestReviewedNotification extends Notification
{
    use Queueable, HasFcmSupport;

    public function __construct(
        public LeaveRequest $leaveRequest,
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
        $request  = $this->leaveRequest;
        $typeName = $request->leaveType?->name ?? 'Cuti';
        $start    = $request->start_date->format('d M Y');
        $end      = $request->end_date->format('d M Y');
        $reviewer = $this->reviewer->name;

        $period = $start === $end ? $start : "{$start} – {$end}";

        return [
            'format'    => 'filament',
            'title'     => $this->approved ? 'Pengajuan Cuti Disetujui' : 'Pengajuan Cuti Ditolak',
            'body'      => $this->approved
                ? "Pengajuan {$typeName} Anda untuk tanggal {$period} telah disetujui oleh {$reviewer}."
                : "Pengajuan {$typeName} Anda untuk tanggal {$period} ditolak oleh {$reviewer}.",
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
                    'url'                   => FilamentUrlHelper::leaveRequestUrl($request),
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
        return 'leave_request_reviewed';
    }

    protected function getRelatedId(): ?string
    {
        return (string) $this->leaveRequest->id;
    }

    protected function getRelatedType(): ?string
    {
        return 'leave_request';
    }
}

<?php

namespace App\Notifications;

use App\Channels\FcmChannel;
use App\Models\Employee;
use App\Notifications\Concerns\HasFcmSupport;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class EmployeeAbsentNotification extends Notification
{
    use Queueable, HasFcmSupport;

    public function __construct(
        public Employee $employee,
        public string $date
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
        return [
            'format' => 'filament',
            'title' => 'Karyawan Tidak Hadir',
            'body' => "{$this->employee->name} tidak hadir pada tanggal {$this->date}",
            'icon' => 'heroicon-o-user-minus',
            'iconColor' => 'warning',
            'duration' => 'persistent',
            'color' => null,
            'status' => 'warning',
            'view' => 'filament-notifications::notification',
            'viewData' => [],
            'actions' => [],
        ];
    }

    public function toArray($notifiable): array
    {
        return $this->toDatabase($notifiable);
    }

    protected function getFcmType(): string
    {
        return 'employee_absent';
    }

    protected function getRelatedId(): ?string
    {
        return (string) $this->employee->id;
    }

    protected function getRelatedType(): ?string
    {
        return 'employee';
    }
}

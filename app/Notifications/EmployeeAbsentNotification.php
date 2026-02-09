<?php

namespace App\Notifications;

use App\Models\Employee;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class EmployeeAbsentNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Employee $employee,
        public string $date
    ) {
    }

    public function via($notifiable): array
    {
        return ['database'];
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
}

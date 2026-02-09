<?php

namespace App\Notifications;

use App\Domain\Payroll\Models\PayrollPeriod;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class PayrollFinalizedEmployeeNotification extends Notification
{
    use Queueable;

    public function __construct(
        public PayrollPeriod $payrollPeriod
    ) {
    }

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        $period = $this->payrollPeriod;

        return [
            'format' => 'filament',
            'title' => 'Slip Gaji Tersedia',
            'body' => "Penggajian untuk periode {$period->period_start} - {$period->period_end} telah difinalisasi. Cek slip gaji Anda!",
            'icon' => 'heroicon-o-banknotes',
            'iconColor' => 'success',
            'duration' => 'persistent',
            'color' => null,
            'status' => 'success',
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

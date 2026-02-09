<?php

namespace App\Notifications;

use App\Channels\FcmChannel;
use App\Domain\Payroll\Models\PayrollPeriod;
use App\Notifications\Concerns\HasFcmSupport;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class PayrollFinalizedEmployeeNotification extends Notification
{
    use Queueable, HasFcmSupport;

    public function __construct(
        public PayrollPeriod $payrollPeriod
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

    protected function getFcmType(): string
    {
        return 'payroll_finalized_employee';
    }

    protected function getRelatedId(): ?string
    {
        return (string) $this->payrollPeriod->id;
    }

    protected function getRelatedType(): ?string
    {
        return 'payroll_period';
    }
}

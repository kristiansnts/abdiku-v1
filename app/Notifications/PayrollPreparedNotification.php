<?php

namespace App\Notifications;

use App\Channels\FcmChannel;
use App\Domain\Payroll\Models\PayrollPeriod;
use App\Helpers\FilamentUrlHelper;
use App\Notifications\Concerns\HasFcmSupport;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class PayrollPreparedNotification extends Notification
{
    use Queueable, HasFcmSupport;

    public function __construct(
        public PayrollPeriod $payrollPeriod,
        public int $employeeCount
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
            'title' => 'Penggajian Siap untuk Ditinjau',
            'body' => "Penggajian untuk periode {$period->period_start} - {$period->period_end} telah disiapkan. {$this->employeeCount} karyawan diproses.",
            'icon' => 'heroicon-o-clipboard-document-check',
            'iconColor' => 'info',
            'duration' => 'persistent',
            'color' => null,
            'status' => 'info',
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
                    'url' => FilamentUrlHelper::payrollPeriodUrl($period),
                    'view' => 'filament-notifications::actions.button-action',
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
        return 'payroll_prepared';
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

<?php

namespace App\Notifications;

use App\Channels\FcmChannel;
use App\Domain\Payroll\Models\PayrollRow;
use App\Helpers\FilamentUrlHelper;
use App\Notifications\Concerns\HasFcmSupport;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class PayslipAvailableNotification extends Notification
{
    use Queueable, HasFcmSupport;

    public function __construct(
        public PayrollRow $payrollRow
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
        $row = $this->payrollRow;
        $period = $row->payrollBatch->payrollPeriod;
        $netSalary = number_format($row->net_salary, 0, ',', '.');

        return [
            'format' => 'filament',
            'title' => 'Slip Gaji Anda Tersedia',
            'body' => "Slip gaji untuk periode {$period->period_start} - {$period->period_end} sudah tersedia. Gaji bersih: Rp {$netSalary}",
            'icon' => 'heroicon-o-document-text',
            'iconColor' => 'success',
            'duration' => 'persistent',
            'color' => null,
            'status' => 'success',
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
                    'label' => 'Lihat Slip Gaji',
                    'shouldClose' => false,
                    'shouldMarkAsRead' => true,
                    'shouldMarkAsUnread' => false,
                    'shouldOpenUrlInNewTab' => false,
                    'size' => 'sm',
                    'tooltip' => null,
                    'url' => FilamentUrlHelper::payslipUrl($row),
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
        return 'payslip_available';
    }

    protected function getRelatedId(): ?string
    {
        return (string) $this->payrollRow->id;
    }

    protected function getRelatedType(): ?string
    {
        return 'payroll_row';
    }
}

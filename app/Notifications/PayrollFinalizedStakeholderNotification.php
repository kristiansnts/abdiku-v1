<?php

namespace App\Notifications;

use App\Channels\FcmChannel;
use App\Domain\Payroll\Models\PayrollBatch;
use App\Domain\Payroll\Models\PayrollPeriod;
use App\Helpers\FilamentUrlHelper;
use App\Notifications\Concerns\HasFcmSupport;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class PayrollFinalizedStakeholderNotification extends Notification
{
    use Queueable, HasFcmSupport;

    public function __construct(
        public PayrollPeriod $payrollPeriod,
        public PayrollBatch $payrollBatch
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
            'title' => 'Penggajian Berhasil Difinalisasi',
            'body' => "Penggajian untuk periode {$period->period_start} - {$period->period_end} telah difinalisasi.",
            'icon' => 'heroicon-o-banknotes',
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
                    'label' => 'Lihat Batch',
                    'shouldClose' => false,
                    'shouldMarkAsRead' => true,
                    'shouldMarkAsUnread' => false,
                    'shouldOpenUrlInNewTab' => false,
                    'size' => 'sm',
                    'tooltip' => null,
                    'url' => FilamentUrlHelper::payrollBatchUrl($this->payrollBatch),
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
        return 'payroll_finalized_stakeholder';
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

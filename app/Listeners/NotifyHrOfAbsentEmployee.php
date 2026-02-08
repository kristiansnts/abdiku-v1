<?php

namespace App\Listeners;

use App\Events\EmployeeAbsentDetected;
use App\Helpers\NotificationRecipientHelper;
use Filament\Notifications\Notification;

class NotifyHrOfAbsentEmployee
{
    public function handle(EmployeeAbsentDetected $event): void
    {
        $employee = $event->employee;
        $date = $event->date;
        $hrUsers = NotificationRecipientHelper::getHrUsers($event->company->id);

        foreach ($hrUsers as $hrUser) {
            Notification::make()
                ->title('Karyawan Tidak Hadir')
                ->body("{$employee->name} tidak hadir pada tanggal {$date}")
                ->icon('heroicon-o-user-minus')
                ->iconColor('warning')
                ->status('warning')
                ->sendToDatabase($hrUser);
        }
    }
}

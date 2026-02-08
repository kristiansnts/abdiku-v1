<?php

namespace App\Listeners;

use App\Events\AttendanceRequestSubmitted;
use App\Helpers\FilamentUrlHelper;
use App\Helpers\NotificationRecipientHelper;
use Filament\Notifications\Actions\Action;
use Filament\Notifications\Notification;

class NotifyHrOfAttendanceRequest
{
    public function handle(AttendanceRequestSubmitted $event): void
    {
        $request = $event->attendanceRequest;
        $hrUsers = NotificationRecipientHelper::getHrUsers($request->company_id);

        // Get date from attendanceRaw relationship or requested_clock_in_at
        $date = $request->attendanceRaw?->date ?? $request->requested_clock_in_at?->format('Y-m-d') ?? 'N/A';

        foreach ($hrUsers as $hrUser) {
            Notification::make()
                ->title('Pengajuan Kehadiran Baru')
                ->body("Pengajuan kehadiran baru dari {$request->employee->name} untuk tanggal {$date}")
                ->icon('heroicon-o-document-text')
                ->iconColor('info')
                ->actions([
                    Action::make('view')
                        ->label('Lihat')
                        ->button()
                        ->url(FilamentUrlHelper::attendanceRequestUrl($request))
                        ->markAsRead(),
                    Action::make('dismiss')
                        ->label('Tutup')
                        ->link()
                        ->markAsRead()
                ])
                ->sendToDatabase($hrUser);
        }
    }
}

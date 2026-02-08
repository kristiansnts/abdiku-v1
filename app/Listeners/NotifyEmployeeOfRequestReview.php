<?php

namespace App\Listeners;

use App\Events\AttendanceRequestReviewed;
use App\Helpers\FilamentUrlHelper;
use Filament\Notifications\Actions\Action;
use Filament\Notifications\Notification;

class NotifyEmployeeOfRequestReview
{
    public function handle(AttendanceRequestReviewed $event): void
    {
        $request = $event->attendanceRequest;
        $user = $request->employee->user;

        if (!$user) {
            return;
        }

        $approved = $event->approved;
        $reviewerName = $event->reviewer->name;

        // Get date from attendanceRaw relationship or requested_clock_in_at
        $date = $request->attendanceRaw?->date ?? $request->requested_clock_in_at?->format('Y-m-d') ?? 'N/A';

        Notification::make()
            ->title($approved ? 'Pengajuan Kehadiran Disetujui' : 'Pengajuan Kehadiran Ditolak')
            ->body($approved
                ? "Pengajuan kehadiran Anda untuk tanggal {$date} telah disetujui oleh {$reviewerName}."
                : "Pengajuan kehadiran Anda untuk tanggal {$date} ditolak oleh {$reviewerName}.")
            ->icon($approved ? 'heroicon-o-check-circle' : 'heroicon-o-x-circle')
            ->iconColor($approved ? 'success' : 'danger')
            ->status($approved ? 'success' : 'warning')
            ->actions([
                Action::make('view')
                    ->label('Lihat Detail')
                    ->button()
                    ->url(FilamentUrlHelper::attendanceRequestUrl($request))
                    ->markAsRead(),
            ])
            ->sendToDatabase($user);
    }
}

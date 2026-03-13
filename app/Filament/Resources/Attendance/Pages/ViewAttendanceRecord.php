<?php

declare(strict_types=1);

namespace App\Filament\Resources\Attendance\Pages;

use App\Domain\Attendance\Services\ApproveAttendanceRecordService;
use App\Domain\Attendance\Services\RejectAttendanceRecordService;
use App\Filament\Resources\Attendance\AttendanceRecordResource;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

final class ViewAttendanceRecord extends ViewRecord
{
    protected static string $resource = AttendanceRecordResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('approve')
                ->label('Setujui')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Setujui Kehadiran')
                ->modalDescription('Apakah Anda yakin ingin menyetujui rekap kehadiran ini?')
                ->form([
                    Textarea::make('review_note')
                        ->label('Catatan (opsional)')
                        ->rows(2),
                ])
                ->visible(fn() => $this->record->isPending() && $this->record->canBeModified())
                ->action(function(array $data) {
                    try {
                        app(ApproveAttendanceRecordService::class)
                            ->execute($this->record, $data['review_note'] ?? null, auth()->user());
                        Notification::make()->title('Kehadiran disetujui')->success()->send();
                        $this->refreshFormData(['status', 'reviewed_by', 'reviewed_at', 'review_note']);
                    } catch (\Throwable $e) {
                        Notification::make()->title($e->getMessage())->danger()->send();
                    }
                }),
            Action::make('reject')
                ->label('Tolak')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->form([
                    Textarea::make('review_note')
                        ->label('Alasan penolakan')
                        ->required()
                        ->rows(2),
                ])
                ->visible(fn() => $this->record->isPending() && $this->record->canBeModified())
                ->action(function(array $data) {
                    try {
                        app(RejectAttendanceRecordService::class)
                            ->execute($this->record, $data['review_note'], auth()->user());
                        Notification::make()->title('Kehadiran ditolak')->warning()->send();
                        $this->refreshFormData(['status', 'reviewed_by', 'reviewed_at', 'review_note']);
                    } catch (\Throwable $e) {
                        Notification::make()->title($e->getMessage())->danger()->send();
                    }
                }),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Informasi Kehadiran')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('employee.name')
                            ->label('Karyawan'),
                        TextEntry::make('date')
                            ->label('Tanggal')
                            ->date('d M Y'),
                        TextEntry::make('clock_in')
                            ->label('Jam Masuk')
                            ->dateTime('H:i')
                            ->timezone('Asia/Jakarta'),
                        TextEntry::make('clock_out')
                            ->label('Jam Keluar')
                            ->dateTime('H:i')
                            ->timezone('Asia/Jakarta')
                            ->placeholder('-'),
                        TextEntry::make('source')
                            ->label('Sumber')
                            ->badge(),
                        TextEntry::make('status')
                            ->label('Status')
                            ->badge(),
                    ]),
                Section::make('Lokasi Kehadiran')
                    ->visible(fn ($record) => $record->company_location_id !== null || $record->evidences()->where('type', 'GEOLOCATION')->exists())
                    ->schema([
                        TextEntry::make('companyLocation.name')
                            ->label('Nama Lokasi')
                            ->placeholder('Di luar area terdaftar'),
                        TextEntry::make('companyLocation.address')
                            ->label('Alamat')
                            ->placeholder('-')
                            ->columnSpanFull(),
                        ViewEntry::make('map')
                            ->label('')
                            ->view('filament.infolists.entries.location-map')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                Section::make('Riwayat')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('created_at')
                            ->label('Dibuat')
                            ->dateTime('d M Y H:i')
                            ->timezone('Asia/Jakarta'),
                        TextEntry::make('updated_at')
                            ->label('Diperbarui')
                            ->dateTime('d M Y H:i')
                            ->timezone('Asia/Jakarta'),
                    ]),
                Section::make('Informasi Review')
                    ->visible(fn($record) => $record->reviewed_by !== null)
                    ->columns(2)
                    ->schema([
                        TextEntry::make('reviewedBy.name')
                            ->label('Direview oleh'),
                        TextEntry::make('reviewed_at')
                            ->label('Waktu Review')
                            ->dateTime('d M Y H:i')
                            ->timezone('Asia/Jakarta'),
                        TextEntry::make('review_note')
                            ->label('Catatan Review')
                            ->placeholder('-')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}

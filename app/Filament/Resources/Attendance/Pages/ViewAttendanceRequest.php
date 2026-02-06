<?php

declare(strict_types=1);

namespace App\Filament\Resources\Attendance\Pages;

use App\Domain\Attendance\Services\ApproveAttendanceRequestService;
use App\Domain\Attendance\Services\RejectAttendanceRequestService;
use App\Filament\Resources\Attendance\AttendanceRequestResource;
use Filament\Actions;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

final class ViewAttendanceRequest extends ViewRecord
{
    protected static string $resource = AttendanceRequestResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Informasi Karyawan')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('employee.name')
                            ->label('Karyawan'),
                        TextEntry::make('employee.employee_id')
                            ->label('ID Karyawan'),
                    ]),
                Section::make('Detail Pengajuan')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('request_type')
                            ->label('Jenis Pengajuan')
                            ->badge(),
                        TextEntry::make('status')
                            ->label('Status')
                            ->badge(),
                        TextEntry::make('requested_clock_in_at')
                            ->label('Jam Masuk Diajukan')
                            ->dateTime('d M Y H:i')
                            ->timezone('Asia/Jakarta')
                            ->placeholder('-'),
                        TextEntry::make('requested_clock_out_at')
                            ->label('Jam Keluar Diajukan')
                            ->dateTime('d M Y H:i')
                            ->timezone('Asia/Jakarta')
                            ->placeholder('-'),
                        TextEntry::make('reason')
                            ->label('Alasan')
                            ->columnSpanFull(),
                        TextEntry::make('requested_at')
                            ->label('Tanggal Pengajuan')
                            ->dateTime('d M Y H:i')
                            ->timezone('Asia/Jakarta'),
                    ]),
                Section::make('Informasi Review')
                    ->columns(2)
                    ->visible(fn ($record) => !$record->isPending())
                    ->schema([
                        TextEntry::make('reviewer.name')
                            ->label('Direview Oleh'),
                        TextEntry::make('reviewed_at')
                            ->label('Tanggal Review')
                            ->dateTime('d M Y H:i')
                            ->timezone('Asia/Jakarta'),
                        TextEntry::make('review_note')
                            ->label('Catatan Review')
                            ->columnSpanFull()
                            ->placeholder('-'),
                    ]),
                Section::make('Koreksi Diterapkan')
                    ->columns(2)
                    ->visible(fn ($record) => $record->hasAppliedCorrection())
                    ->icon('heroicon-o-check-badge')
                    ->iconColor('success')
                    ->schema([
                        TextEntry::make('timeCorrection.corrected_clock_in')
                            ->label('Jam Masuk Dikoreksi')
                            ->dateTime('d M Y H:i')
                            ->timezone('Asia/Jakarta')
                            ->placeholder('-'),
                        TextEntry::make('timeCorrection.corrected_clock_out')
                            ->label('Jam Keluar Dikoreksi')
                            ->dateTime('d M Y H:i')
                            ->timezone('Asia/Jakarta')
                            ->placeholder('-'),
                        TextEntry::make('timeCorrection.approvedBy.name')
                            ->label('Disetujui Oleh'),
                        TextEntry::make('timeCorrection.approved_at')
                            ->label('Tanggal Persetujuan')
                            ->dateTime('d M Y H:i')
                            ->timezone('Asia/Jakarta'),
                    ]),
                Section::make('Data Kehadiran Terkait')
                    ->columns(2)
                    ->visible(fn ($record) => $record->attendance_raw_id !== null)
                    ->schema([
                        TextEntry::make('attendanceRaw.date')
                            ->label('Tanggal')
                            ->date('d M Y'),
                        TextEntry::make('attendanceRaw.source')
                            ->label('Sumber')
                            ->badge(),
                        TextEntry::make('attendanceRaw.clock_in')
                            ->label('Jam Masuk Tercatat')
                            ->dateTime('H:i')
                            ->timezone('Asia/Jakarta')
                            ->placeholder('-'),
                        TextEntry::make('attendanceRaw.clock_out')
                            ->label('Jam Keluar Tercatat')
                            ->dateTime('H:i')
                            ->timezone('Asia/Jakarta')
                            ->placeholder('-'),
                    ]),
                Section::make('Lokasi Kehadiran')
                    ->visible(fn ($record) => $record->attendanceRaw?->company_location_id !== null)
                    ->schema([
                        TextEntry::make('attendanceRaw.companyLocation.name')
                            ->label('Nama Lokasi'),
                        TextEntry::make('attendanceRaw.companyLocation.address')
                            ->label('Alamat')
                            ->columnSpanFull(),
                        ViewEntry::make('map')
                            ->label('')
                            ->view('filament.infolists.entries.attendance-request-location-map')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('approve')
                ->label('Setujui')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Setujui Pengajuan')
                ->modalDescription('Pengajuan akan disetujui dan koreksi waktu akan diterapkan ke sistem payroll.')
                ->form([
                    Textarea::make('review_note')
                        ->label('Catatan (Opsional)')
                        ->rows(3),
                ])
                ->visible(fn () => $this->record->isPending())
                ->action(function (array $data): void {
                    try {
                        app(ApproveAttendanceRequestService::class)->execute(
                            request: $this->record,
                            reviewNote: $data['review_note'] ?? null,
                            actor: auth()->user(),
                        );

                        Notification::make()
                            ->title('Pengajuan disetujui')
                            ->body('Koreksi waktu telah diterapkan dan akan mempengaruhi perhitungan payroll.')
                            ->success()
                            ->send();

                        $this->redirect($this->getResource()::getUrl('index'));
                    } catch (\DomainException $e) {
                        Notification::make()
                            ->title('Tidak dapat menyetujui pengajuan')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
            Actions\Action::make('reject')
                ->label('Tolak')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Tolak Pengajuan')
                ->modalDescription('Apakah Anda yakin ingin menolak pengajuan ini?')
                ->form([
                    Textarea::make('review_note')
                        ->label('Alasan Penolakan')
                        ->required()
                        ->rows(3),
                ])
                ->visible(fn () => $this->record->isPending())
                ->action(function (array $data): void {
                    try {
                        app(RejectAttendanceRequestService::class)->execute(
                            request: $this->record,
                            reviewNote: $data['review_note'],
                            actor: auth()->user(),
                        );

                        Notification::make()
                            ->title('Pengajuan ditolak')
                            ->success()
                            ->send();

                        $this->redirect($this->getResource()::getUrl('index'));
                    } catch (\DomainException $e) {
                        Notification::make()
                            ->title('Tidak dapat menolak pengajuan')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }
}

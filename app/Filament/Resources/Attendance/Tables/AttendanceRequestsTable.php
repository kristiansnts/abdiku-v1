<?php

declare(strict_types=1);

namespace App\Filament\Resources\Attendance\Tables;

use App\Domain\Attendance\Enums\AttendanceRequestType;
use App\Domain\Attendance\Enums\AttendanceStatus;
use App\Domain\Attendance\Services\ApproveAttendanceRequestService;
use App\Domain\Attendance\Services\RejectAttendanceRequestService;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

final class AttendanceRequestsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('employee.name')
                    ->label('Karyawan')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('request_type')
                    ->label('Jenis Pengajuan')
                    ->badge(),
                TextColumn::make('requested_clock_in_at')
                    ->label('Jam Masuk Diajukan')
                    ->dateTime('d M Y H:i')
                    ->timezone('Asia/Jakarta')
                    ->sortable(),
                TextColumn::make('requested_clock_out_at')
                    ->label('Jam Keluar Diajukan')
                    ->dateTime('d M Y H:i')
                    ->timezone('Asia/Jakarta')
                    ->sortable(),
                TextColumn::make('reason')
                    ->label('Alasan')
                    ->limit(50)
                    ->tooltip(fn($record) => $record->reason),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge(),
                TextColumn::make('requested_at')
                    ->label('Tanggal Pengajuan')
                    ->dateTime('d M Y H:i')
                    ->timezone('Asia/Jakarta')
                    ->sortable(),
                TextColumn::make('reviewer.name')
                    ->label('Direview Oleh')
                    ->placeholder('-'),
            ])
            ->defaultSort('requested_at', 'desc')
            ->filters([
                SelectFilter::make('request_type')
                    ->label('Jenis Pengajuan')
                    ->options(AttendanceRequestType::class),
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(AttendanceStatus::class),
            ])
            ->actions([
                ViewAction::make(),
                Action::make('approve')
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
                    ->visible(fn($record) => $record->isPending())
                    ->action(function ($record, array $data) {
                        try {
                            app(ApproveAttendanceRequestService::class)->execute(
                                request: $record,
                                reviewNote: $data['review_note'] ?? null,
                                actor: auth()->user(),
                            );

                            Notification::make()
                                ->title('Pengajuan disetujui')
                                ->body('Koreksi waktu telah diterapkan dan akan mempengaruhi perhitungan payroll.')
                                ->success()
                                ->send();
                        } catch (\DomainException $e) {
                            Notification::make()
                                ->title('Tidak dapat menyetujui pengajuan')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                Action::make('reject')
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
                    ->visible(fn($record) => $record->isPending())
                    ->action(function ($record, array $data) {
                        try {
                            app(RejectAttendanceRequestService::class)->execute(
                                request: $record,
                                reviewNote: $data['review_note'],
                                actor: auth()->user(),
                            );

                            Notification::make()
                                ->title('Pengajuan ditolak')
                                ->success()
                                ->send();
                        } catch (\DomainException $e) {
                            Notification::make()
                                ->title('Tidak dapat menolak pengajuan')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ]);
    }
}

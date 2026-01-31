<?php

declare(strict_types=1);

namespace App\Filament\Resources\Payroll\Tables;

use App\Domain\Attendance\Enums\AttendanceClassification;
use App\Domain\Payroll\Exceptions\InvalidPayrollStateException;
use App\Domain\Payroll\Exceptions\UnauthorizedPayrollActionException;
use App\Domain\Payroll\Services\ApproveOverrideService;
use Filament\Tables\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

final class OverrideRequestsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('attendanceDecision.employee.name')
                    ->label('Karyawan')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('attendanceDecision.date')
                    ->label('Tanggal')
                    ->date()
                    ->sortable(),
                TextColumn::make('old_classification')
                    ->label('Saat Ini')
                    ->badge(),
                TextColumn::make('proposed_classification')
                    ->label('Usulan')
                    ->badge(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'PENDING' => 'warning',
                        'APPROVED' => 'success',
                        'REJECTED' => 'danger',
                    }),
                TextColumn::make('requestedBy.name')
                    ->label('Diminta Oleh')
                    ->sortable(),
                TextColumn::make('requested_at')
                    ->label('Diminta Pada')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('requested_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'PENDING' => 'Menunggu',
                        'APPROVED' => 'Disetujui',
                        'REJECTED' => 'Ditolak',
                    ])
                    ->default('PENDING'),
            ])
            ->actions([
                Action::make('approve')
                    ->label('Setujui')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn($record): bool => $record->status === 'PENDING')
                    ->requiresConfirmation()
                    ->modalHeading('Setujui Permintaan Penyesuaian')
                    ->modalDescription('Ini akan mengubah klasifikasi kehadiran sesuai permintaan.')
                    ->form([
                        Textarea::make('review_note')
                            ->label('Catatan Tinjauan (Opsional)')
                            ->maxLength(500),
                    ])
                    ->action(function ($record, array $data) {
                        try {
                            app(ApproveOverrideService::class)->execute(
                                request: $record,
                                approved: true,
                                reviewNote: $data['review_note'] ?? null,
                                actor: auth()->user(),
                            );

                            Notification::make()
                                ->title('Penyesuaian disetujui')
                                ->body('Klasifikasi kehadiran telah diperbarui.')
                                ->success()
                                ->send();
                        } catch (InvalidPayrollStateException $e) {
                            Notification::make()
                                ->title('Tidak dapat menyetujui penyesuaian')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        } catch (UnauthorizedPayrollActionException $e) {
                            Notification::make()
                                ->title('Tidak diizinkan')
                                ->body('Hanya pemilik yang dapat menyetujui permintaan penyesuaian.')
                                ->danger()
                                ->send();
                        } catch (\DomainException $e) {
                            Notification::make()
                                ->title('Tidak dapat menyetujui penyesuaian')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                Action::make('reject')
                    ->label('Tolak')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn($record): bool => $record->status === 'PENDING')
                    ->requiresConfirmation()
                    ->modalHeading('Tolak Permintaan Penyesuaian')
                    ->modalDescription('Klasifikasi kehadiran tidak akan berubah.')
                    ->form([
                        Textarea::make('review_note')
                            ->label('Alasan Penolakan')
                            ->required()
                            ->maxLength(500),
                    ])
                    ->action(function ($record, array $data) {
                        try {
                            app(ApproveOverrideService::class)->execute(
                                request: $record,
                                approved: false,
                                reviewNote: $data['review_note'],
                                actor: auth()->user(),
                            );

                            Notification::make()
                                ->title('Penyesuaian ditolak')
                                ->success()
                                ->send();
                        } catch (UnauthorizedPayrollActionException $e) {
                            Notification::make()
                                ->title('Tidak diizinkan')
                                ->body('Hanya pemilik yang dapat menolak permintaan penyesuaian.')
                                ->danger()
                                ->send();
                        } catch (\DomainException $e) {
                            Notification::make()
                                ->title('Tidak dapat menolak penyesuaian')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ]);
    }
}

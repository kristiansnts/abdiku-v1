<?php

declare(strict_types=1);

namespace App\Filament\Resources\Attendance\Tables;

use App\Domain\Attendance\Enums\AttendanceSource;
use App\Domain\Attendance\Enums\AttendanceStatus;
use App\Domain\Attendance\Services\ApproveAttendanceRecordService;
use App\Domain\Attendance\Services\RejectAttendanceRecordService;
use App\Models\CompanyLocation;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

final class AttendanceRecordsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('employee.name')
                    ->label('Karyawan')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('date')
                    ->label('Tanggal')
                    ->date()
                    ->sortable(),
                TextColumn::make('clock_in')
                    ->label('Jam Masuk')
                    ->time()
                    ->timezone('Asia/Jakarta')
                    ->sortable(),
                TextColumn::make('clock_out')
                    ->label('Jam Keluar')
                    ->time()
                    ->timezone('Asia/Jakarta')
                    ->sortable(),
                TextColumn::make('companyLocation.name')
                    ->label('Lokasi')
                    ->placeholder('—')
                    ->badge()
                    ->color('info')
                    ->sortable(),
                TextColumn::make('source')
                    ->label('Sumber')
                    ->badge(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->sortable(),
            ])
            ->defaultSort('date', 'desc')
            ->filters([
                SelectFilter::make('company_location_id')
                    ->label('Lokasi')
                    ->options(fn() => CompanyLocation::query()
                        ->where('company_id', auth()->user()?->company_id)
                        ->pluck('name', 'id')
                        ->toArray()
                    )
                    ->placeholder('Semua Lokasi')
                    ->native(false),
                SelectFilter::make('source')
                    ->label('Sumber')
                    ->options(AttendanceSource::class),
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(AttendanceStatus::class)
                    ->placeholder('Semua Status')
                    ->native(false),
            ])
            ->actions([
                \Filament\Tables\Actions\ViewAction::make(),
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
                    ->visible(fn($record) => $record->isPending() && $record->canBeModified())
                    ->action(function($record, array $data) {
                        try {
                            app(ApproveAttendanceRecordService::class)
                                ->execute($record, $data['review_note'] ?? null, auth()->user());
                            Notification::make()->title('Kehadiran disetujui')->success()->send();
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
                    ->visible(fn($record) => $record->isPending() && $record->canBeModified())
                    ->action(function($record, array $data) {
                        try {
                            app(RejectAttendanceRecordService::class)
                                ->execute($record, $data['review_note'], auth()->user());
                            Notification::make()->title('Kehadiran ditolak')->warning()->send();
                        } catch (\Throwable $e) {
                            Notification::make()->title($e->getMessage())->danger()->send();
                        }
                    }),
                \Filament\Tables\Actions\Action::make('viewLocation')
                    ->label('Lihat Lokasi')
                    ->icon('heroicon-o-map-pin')
                    ->color('info')
                    ->modalHeading('Lokasi Kehadiran')
                    ->modalSubmitAction(false)
                    ->modalCancelAction(false)
                    ->visible(fn($record) => $record->company_location_id !== null || $record->evidences()->where('type', 'GEOLOCATION')->exists())
                    ->form(fn($record) => [
                        \App\Filament\Forms\Components\LocationMapPicker::make('location')
                            ->label('')
                            ->latitude($record->companyLocation?->latitude)
                            ->longitude($record->companyLocation?->longitude)
                            ->radius($record->companyLocation?->geofence_radius_meters)
                            ->address($record->companyLocation?->address)
                            ->employeeLatitude(function() use ($record) {
                                $ev = $record->evidences()->where('type', 'GEOLOCATION')->where('action', 'CLOCK_IN')->first();
                                $payload = $ev ? (is_array($ev->payload) ? $ev->payload : json_decode($ev->payload, true)) : null;
                                return $payload['lat'] ?? null;
                            })
                            ->employeeLongitude(function() use ($record) {
                                $ev = $record->evidences()->where('type', 'GEOLOCATION')->where('action', 'CLOCK_IN')->first();
                                $payload = $ev ? (is_array($ev->payload) ? $ev->payload : json_decode($ev->payload, true)) : null;
                                return $payload['lng'] ?? null;
                            })
                            ->withinGeofence(function() use ($record) {
                                $ev = $record->evidences()->where('type', 'GEOLOCATION')->where('action', 'CLOCK_IN')->first();
                                $payload = $ev ? (is_array($ev->payload) ? $ev->payload : json_decode($ev->payload, true)) : null;
                                return $payload['within_geofence'] ?? true;
                            })
                            ->disabled()
                    ]),
            ]);
    }
}

<?php

declare(strict_types=1);

namespace App\Filament\Resources\Attendance\Tables;

use App\Domain\Attendance\Enums\AttendanceSource;
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
                TextColumn::make('source')
                    ->label('Sumber')
                    ->badge(),
            ])
            ->defaultSort('date', 'desc')
            ->filters([
                SelectFilter::make('source')
                    ->label('Sumber')
                    ->options(AttendanceSource::class),
            ])
            ->actions([
                \Filament\Tables\Actions\ViewAction::make(),
                \Filament\Tables\Actions\Action::make('viewLocation')
                    ->label('Lihat Lokasi')
                    ->icon('heroicon-o-map-pin')
                    ->color('info')
                    ->modalHeading('Lokasi Kehadiran')
                    ->modalSubmitAction(false)
                    ->modalCancelAction(false)
                    ->visible(fn($record) => $record->company_location_id !== null)
                    ->form(fn($record) => [
                        \App\Filament\Forms\Components\LocationMapPicker::make('location')
                            ->label('')
                            ->latitude($record->companyLocation?->latitude)
                            ->longitude($record->companyLocation?->longitude)
                            ->radius($record->companyLocation?->geofence_radius_meters)
                            ->address($record->companyLocation?->address)
                            ->disabled()
                    ]),
            ]);
    }
}

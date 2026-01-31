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
                    ->sortable(),
                TextColumn::make('clock_out')
                    ->label('Jam Keluar')
                    ->time()
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
            ]);
    }
}

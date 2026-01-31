<?php

declare(strict_types=1);

namespace App\Filament\Resources\Payroll\Tables;

use App\Domain\Attendance\Enums\AttendanceClassification;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

final class PayrollOverridesTable
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
                    ->label('Dari')
                    ->badge(),
                TextColumn::make('new_classification')
                    ->label('Ke')
                    ->badge(),
                TextColumn::make('overriddenBy.name')
                    ->label('Disetujui Oleh')
                    ->sortable(),
                TextColumn::make('overridden_at')
                    ->label('Disetujui Pada')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('overridden_at', 'desc')
            ->actions([
            ]);
    }
}

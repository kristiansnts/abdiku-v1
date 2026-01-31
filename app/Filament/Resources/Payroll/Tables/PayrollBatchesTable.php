<?php

declare(strict_types=1);

namespace App\Filament\Resources\Payroll\Tables;

use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

final class PayrollBatchesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('payrollPeriod.period_start')
                    ->label('Mulai Periode')
                    ->date()
                    ->sortable(),
                TextColumn::make('payrollPeriod.period_end')
                    ->label('Selesai Periode')
                    ->date()
                    ->sortable(),
                TextColumn::make('total_amount')
                    ->label('Total Biaya')
                    ->money('IDR')
                    ->sortable(),
                TextColumn::make('rows_count')
                    ->label('Karyawan')
                    ->counts('rows')
                    ->sortable(),
                TextColumn::make('finalizedBy.name')
                    ->label('Difinalisasi Oleh')
                    ->sortable(),
                TextColumn::make('finalized_at')
                    ->label('Difinalisasi Pada')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('finalized_at', 'desc')
            ->actions([
                ViewAction::make(),
            ]);
    }
}

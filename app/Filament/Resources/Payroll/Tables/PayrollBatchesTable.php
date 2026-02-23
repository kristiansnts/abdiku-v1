<?php

declare(strict_types=1);

namespace App\Filament\Resources\Payroll\Tables;

use Filament\Tables\Actions\Action;
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
                Action::make('export')
                    ->label('Export')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('info')
                    ->action(function ($record) {
                        $batch = $record->load('rows.employee', 'payrollPeriod');

                        $periodStart = $batch->payrollPeriod?->period_start?->format('Y-m-d') ?? 'unknown-start';
                        $periodEnd = $batch->payrollPeriod?->period_end?->format('Y-m-d') ?? 'unknown-end';
                        $downloadedAt = now()->format('Y-m-d');
                        $filename = "payroll-batch-{$periodStart}-to-{$periodEnd}-{$downloadedAt}.csv";

                        return response()->streamDownload(function () use ($batch) {
                            $handle = fopen('php://output', 'w');
                            fputcsv($handle, [
                                'employee_id',
                                'employee_name',
                                'gross_amount',
                                'deduction_amount',
                                'tax_amount',
                                'net_amount',
                            ]);

                            foreach ($batch->rows as $row) {
                                fputcsv($handle, [
                                    $row->employee_id,
                                    $row->employee?->name,
                                    $row->gross_amount,
                                    $row->deduction_amount,
                                    $row->tax_amount,
                                    $row->net_amount,
                                ]);
                            }

                            fclose($handle);
                        }, $filename);
                    }),
            ]);
    }
}

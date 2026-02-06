<?php

declare(strict_types=1);

namespace App\Filament\Resources\Payroll\Tables;

use App\Application\Payroll\Services\PayslipPdfService;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

final class PayrollRowsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('employee.name')
                    ->label('Karyawan')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('payrollBatch.payrollPeriod.period_start')
                    ->label('Periode')
                    ->date()
                    ->sortable(),
                TextColumn::make('gross_amount')
                    ->label('Gaji Kotor')
                    ->money('IDR')
                    ->sortable(),
                TextColumn::make('deduction_amount')
                    ->label('Potongan')
                    ->money('IDR')
                    ->sortable(),
                TextColumn::make('net_amount')
                    ->label('Take Home Pay')
                    ->money('IDR')
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('payrollBatch.finalized_at')
                    ->label('Difinalisasi')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->defaultSort('payrollBatch.finalized_at', 'desc')
            ->actions([
                ViewAction::make(),
                Action::make('download')
                    ->label('Unduh')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->visible(fn ($record) => $record->payrollBatch?->finalized_at !== null)
                    ->action(function ($record) {
                        $service = app(PayslipPdfService::class);
                        $pdfContent = $service->generate($record);
                        $filename = $service->generateFilename($record);

                        return response()->streamDownload(
                            fn () => print($pdfContent),
                            $filename,
                            ['Content-Type' => 'application/pdf']
                        );
                    }),
            ]);
    }
}

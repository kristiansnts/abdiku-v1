<?php

declare(strict_types=1);

namespace App\Filament\Resources\Payroll\Tables;

use App\Domain\Payroll\Enums\PayrollState;
use App\Domain\Payroll\Exceptions\InvalidPayrollStateException;
use App\Domain\Payroll\Exceptions\UnauthorizedPayrollActionException;
use App\Domain\Payroll\Models\PayrollPeriod;
use App\Domain\Payroll\Services\FinalizePayrollService;
use App\Domain\Payroll\Services\PreparePayrollService;
use App\Domain\Payroll\Services\ReviewPayrollService;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

final class PayrollPeriodsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('period_start')
                    ->label('Mulai')
                    ->date()
                    ->sortable(),
                TextColumn::make('period_end')
                    ->label('Selesai')
                    ->date()
                    ->sortable(),
                TextColumn::make('state')
                    ->label('Status')
                    ->badge(),
                TextColumn::make('attendance_decisions_count')
                    ->label('Keputusan')
                    ->counts('attendanceDecisions')
                    ->sortable(),
                TextColumn::make('finalized_at')
                    ->label('Difinalisasi Pada')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->defaultSort('period_start', 'desc')
            ->filters([
                SelectFilter::make('state')
                    ->label('Status')
                    ->options(PayrollState::class),
            ])
            ->actions([
                ViewAction::make(),
            ]);
    }
}

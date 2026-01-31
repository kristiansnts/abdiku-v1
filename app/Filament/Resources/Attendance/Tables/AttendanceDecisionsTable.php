<?php

declare(strict_types=1);

namespace App\Filament\Resources\Attendance\Tables;

use App\Domain\Attendance\Enums\AttendanceClassification;
use App\Domain\Payroll\Enums\DeductionType;
use App\Domain\Payroll\Enums\PayrollState;
use App\Domain\Payroll\Exceptions\InvalidPayrollStateException;
use App\Domain\Payroll\Exceptions\UnauthorizedPayrollActionException;
use App\Domain\Payroll\Services\RequestOverrideService;
use Filament\Tables\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

final class AttendanceDecisionsTable
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
                TextColumn::make('classification')
                    ->label('Klasifikasi')
                    ->badge(),
                IconColumn::make('payable')
                    ->label('Dapat Dibayar')
                    ->boolean(),
                TextColumn::make('deduction_type')
                    ->label('Tipe Potongan')
                    ->badge()
                    ->toggleable(),
                TextColumn::make('payrollPeriod.period_start')
                    ->label('Periode')
                    ->date()
                    ->sortable()
                    ->toggleable(),
                IconColumn::make('override')
                    ->label('Disesuaikan')
                    ->boolean()
                    ->getStateUsing(fn($record): bool => $record->override !== null)
                    ->toggleable(),
            ])
            ->defaultSort('date', 'desc')
            ->filters([
                SelectFilter::make('classification')
                    ->label('Klasifikasi')
                    ->options(AttendanceClassification::class),
                SelectFilter::make('payable')
                    ->label('Dapat Dibayar')
                    ->options([
                        '1' => 'Ya',
                        '0' => 'Tidak',
                    ]),
            ])
            ->actions([
                Action::make('requestOverride')
                    ->label('Ajukan Penyesuaian')
                    ->icon('heroicon-o-exclamation-triangle')
                    ->color('warning')
                    ->visible(
                        fn($record): bool =>
                        ($record->payrollPeriod->state === PayrollState::DRAFT ||
                            $record->payrollPeriod->state === PayrollState::REVIEW)
                    )
                    ->modalHeading('Ajukan Penyesuaian Klasifikasi')
                    ->modalDescription('Usulan penyesuaian akan dikirim ke pemilik untuk ditinjau.')
                    ->modalSubmitActionLabel('Ajukan')
                    ->modalWidth('md')
                    ->form([
                        Select::make('proposed_classification')
                            ->label('Usulan Klasifikasi')
                            ->options(AttendanceClassification::class)
                            ->required()
                            ->native(false),
                        Textarea::make('reason')
                            ->label('Alasan Pengajuan Penyesuaian')
                            ->required()
                            ->maxLength(1000)
                            ->rows(4),
                    ])
                    ->action(function ($record, array $data) {
                        try {
                            app(RequestOverrideService::class)->execute(
                                decision: $record,
                                proposedClassification: AttendanceClassification::from($data['proposed_classification']),
                                reason: $data['reason'],
                                actor: auth()->user(),
                            );

                            Notification::make()
                                ->title('Permintaan penyesuaian telah diajukan')
                                ->body('Pemilik akan meninjau permintaan Anda.')
                                ->success()
                                ->send();
                        } catch (InvalidPayrollStateException $e) {
                            Notification::make()
                                ->title('Tidak dapat mengajukan penyesuaian')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        } catch (UnauthorizedPayrollActionException $e) {
                            Notification::make()
                                ->title('Tidak diizinkan')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ]);
    }
}

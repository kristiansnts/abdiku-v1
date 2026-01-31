<?php

declare(strict_types=1);

namespace App\Filament\Resources\Payroll\Tables;

use App\Application\Payroll\Services\BulkThrCalculationApplicationService;
use App\Application\Payroll\Services\ThrPreviewApplicationService;
use App\Domain\Payroll\Contracts\PayrollPeriodRepositoryInterface;
use App\Domain\Payroll\Enums\PayrollAdditionCode;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

final class PayrollAdditionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('employee.name')
                    ->label('Karyawan')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('period.period_start')
                    ->label('Periode')
                    ->formatStateUsing(
                        fn($record) =>
                        $record->period->period_start->format('M Y') . ' - ' .
                        $record->period->period_end->format('M Y')
                    )
                    ->sortable(),

                TextColumn::make('code')
                    ->label('Jenis')
                    ->badge()
                    ->sortable(),

                TextColumn::make('amount')
                    ->label('Jumlah')
                    ->money('IDR')
                    ->sortable(),

                TextColumn::make('description')
                    ->label('Keterangan')
                    ->limit(50)
                    ->toggleable(),

                TextColumn::make('creator.name')
                    ->label('Dibuat oleh')
                    ->toggleable()
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Dibuat pada')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('code')
                    ->label('Jenis Tambahan')
                    ->options(PayrollAdditionCode::class),

                SelectFilter::make('employee_id')
                    ->label('Karyawan')
                    ->relationship('employee', 'name')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('payroll_period_id')
                    ->label('Periode')
                    ->relationship('period', 'id')
                    ->getOptionLabelFromRecordUsing(
                        fn($record) =>
                        $record->period_start->format('M Y') . ' - ' .
                        $record->period_end->format('M Y')
                    ),
            ])
            ->headerActions([
                Action::make('bulk_calculate_thr')
                    ->label('Hitung THR Massal')
                    ->icon('heroicon-o-calculator')
                    ->color('success')
                    ->steps([
                        \Filament\Forms\Components\Wizard\Step::make('setup')
                            ->label('Pengaturan')
                            ->schema([
                                Select::make('payroll_period_id')
                                    ->label('Periode Gaji')
                                    ->options(function (PayrollPeriodRepositoryInterface $periodRepository) {
                                        return $periodRepository->getFormattedOptionsForCompany(auth()->user()?->company_id ?? 0);
                                    })
                                    ->required()
                                    ->live(),

                                Select::make('employee_type')
                                    ->label('Jenis Karyawan Default')
                                    ->options([
                                        'permanent' => 'Karyawan Tetap',
                                        'contract' => 'Karyawan Kontrak',
                                        'daily' => 'Karyawan Harian',
                                        'freelance' => 'Freelance',
                                    ])
                                    ->default('permanent')
                                    ->required()
                                    ->live(),

                                TextInput::make('working_days_in_year')
                                    ->label('Jumlah Hari Kerja dalam Tahun')
                                    ->numeric()
                                    ->default(260)
                                    ->helperText('Untuk karyawan harian/freelance'),
                            ]),

                        \Filament\Forms\Components\Wizard\Step::make('preview')
                            ->label('Preview Perhitungan')
                            ->schema([
                                \Filament\Forms\Components\Placeholder::make('preview_table')
                                    ->label('Daftar Perhitungan THR')
                                    ->content(function (\Filament\Forms\Get $get, ThrPreviewApplicationService $previewService) {
                                        $periodId = $get('payroll_period_id');
                                        $employeeType = $get('employee_type') ?? 'permanent';
                                        $workingDaysInYear = (int) ($get('working_days_in_year') ?? 260);
                                        $companyId = auth()->user()?->company_id ?? 0;

                                        if (!$periodId) {
                                            return 'Silakan pilih periode gaji terlebih dahulu.';
                                        }

                                        return $previewService->generateHtmlPreview(
                                            $companyId,
                                            (int) $periodId,
                                            $employeeType,
                                            $workingDaysInYear
                                        );
                                    })
                                    ->columnSpanFull(),
                            ]),
                    ])
                    ->action(function (array $data, BulkThrCalculationApplicationService $bulkThrService) {
                        try {
                            $companyId = auth()->user()?->company_id ?? 0;
                            $periodId = (int) $data['payroll_period_id'];
                            $employeeType = $data['employee_type'];
                            $workingDaysInYear = (int) ($data['working_days_in_year'] ?? 260);
                            $createdBy = auth()->id() ?? 0;

                            $result = $bulkThrService->executeBulkCalculation(
                                $companyId,
                                $periodId,
                                $createdBy,
                                $employeeType,
                                $workingDaysInYear
                            );

                            if ($result['success_count'] > 0) {
                                Notification::make()
                                    ->title('Berhasil')
                                    ->body("THR berhasil dihitung untuk {$result['success_count']} karyawan")
                                    ->success()
                                    ->send();
                            }

                            if ($result['skipped_count'] > 0) {
                                Notification::make()
                                    ->title('Info')
                                    ->body("{$result['skipped_count']} karyawan dilewati karena sudah memiliki THR")
                                    ->info()
                                    ->send();
                            }

                            if ($result['error_count'] > 0) {
                                Notification::make()
                                    ->title('Ada Error')
                                    ->body("{$result['error_count']} karyawan gagal diproses: " . implode(', ', \array_slice($result['errors'], 0, 3)))
                                    ->warning()
                                    ->send();
                            }

                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Error')
                                ->body('Gagal menjalankan perhitungan THR massal: ' . $e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
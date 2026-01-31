<?php

declare(strict_types=1);

namespace App\Filament\Resources\Payroll\Schemas;

use App\Application\Payroll\DTOs\ThrCalculationRequest;
use App\Application\Payroll\Services\ThrCalculationApplicationService;
use App\Domain\Payroll\Contracts\EmployeeRepositoryInterface;
use App\Domain\Payroll\Contracts\PayrollPeriodRepositoryInterface;
use App\Domain\Payroll\Enums\PayrollAdditionCode;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;

final class PayrollAdditionForm
{
    public static function configure(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Informasi Dasar')
                    ->schema([
                        Select::make('employee_id')
                            ->label('Karyawan')
                            ->options(function (EmployeeRepositoryInterface $employeeRepository) {
                                return $employeeRepository->getActiveEmployeesByCompany(auth()->user()?->company_id ?? 0)
                                    ->pluck('name', 'id')
                                    ->toArray();
                            })
                            ->searchable()
                            ->required()
                            ->live(),

                        Select::make('payroll_period_id')
                            ->label('Periode Gaji')
                            ->options(function (PayrollPeriodRepositoryInterface $periodRepository) {
                                return $periodRepository->getFormattedOptionsForCompany(auth()->user()?->company_id ?? 0);
                            })
                            ->searchable()
                            ->required()
                            ->live(),

                        Select::make('code')
                            ->label('Jenis Tambahan')
                            ->options(PayrollAdditionCode::class)
                            ->required()
                            ->live(),
                    ])
                    ->columns(2),

                Section::make('Perhitungan THR')
                    ->schema([
                        Select::make('employee_type')
                            ->label('Jenis Karyawan')
                            ->options([
                                'permanent' => 'Karyawan Tetap',
                                'contract' => 'Karyawan Kontrak',
                                'daily' => 'Karyawan Harian',
                                'freelance' => 'Freelance',
                            ])
                            ->default('permanent')
                            ->live()
                            ->visible(fn(Get $get) => $get('code') === 'THR'),

                        TextInput::make('working_days_in_year')
                            ->label('Jumlah Hari Kerja dalam Tahun')
                            ->numeric()
                            ->default(260)
                            ->helperText('Untuk karyawan harian/freelance')
                            ->visible(fn(Get $get) => $get('code') === 'THR' && in_array($get('employee_type'), ['daily', 'freelance'])),

                        Actions::make([
                            Action::make('calculate_thr')
                                ->label('Hitung THR Otomatis')
                                ->icon('heroicon-o-calculator')
                                ->color('success')
                                ->action(function (Set $set, Get $get, ThrCalculationApplicationService $thrService) {
                                    $employeeId = $get('employee_id');
                                    $periodId = $get('payroll_period_id');
                                    $employeeType = $get('employee_type') ?? 'permanent';
                                    $workingDaysInYear = (int) ($get('working_days_in_year') ?? 260);
                                    $companyId = auth()->user()?->company_id ?? 0;

                                    if (!$employeeId || !$periodId) {
                                        Notification::make()
                                            ->title('Error')
                                            ->body('Silakan pilih karyawan dan periode gaji terlebih dahulu')
                                            ->danger()
                                            ->send();
                                        return;
                                    }

                                    try {
                                        $request = ThrCalculationRequest::fromArray([
                                            'employee_id' => $employeeId,
                                            'period_id' => $periodId,
                                            'company_id' => $companyId,
                                            'employee_type' => $employeeType,
                                            'working_days_in_year' => $workingDaysInYear,
                                        ]);

                                        $preview = $thrService->getCalculationPreview($request);

                                        if (!$preview['success']) {
                                            throw new \Exception($preview['error']);
                                        }

                                        $result = $preview['result'];
                                        
                                        if (!$result->isEligible()) {
                                            throw new \Exception($result->notes);
                                        }

                                        $set('amount', $result->thrAmount);
                                        $set('description', $result->notes);

                                        Notification::make()
                                            ->title('Berhasil')
                                            ->body("THR berhasil dihitung: " . $result->getFormattedAmount())
                                            ->success()
                                            ->send();

                                    } catch (\Exception $e) {
                                        Notification::make()
                                            ->title('Error')
                                            ->body('Gagal menghitung THR: ' . $e->getMessage())
                                            ->danger()
                                            ->send();
                                    }
                                })
                                ->visible(fn(Get $get) => $get('code') === 'THR'),
                        ]),
                    ])
                    ->visible(fn(Get $get) => $get('code') === 'THR'),

                Section::make('Detail Tambahan')
                    ->schema([
                        TextInput::make('amount')
                            ->label('Jumlah')
                            ->numeric()
                            ->prefix('Rp')
                            ->step(0.01)
                            ->minValue(0)
                            ->required(),

                        Textarea::make('description')
                            ->label('Keterangan')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Section::make('Informasi Sistem')
                    ->schema([
                        Placeholder::make('creator.name')
                            ->label('Dibuat oleh')
                            ->content(fn($record) => $record?->creator?->name ?? auth()->user()?->name),

                        Placeholder::make('created_at')
                            ->label('Dibuat pada')
                            ->content(fn($record) => $record?->created_at?->format('Y-m-d H:i:s')),
                    ])
                    ->columns(2)
                    ->hiddenOn('create'),
            ]);
    }
}
<?php

declare(strict_types=1);

namespace App\Filament\Resources\Payroll\Schemas;

use App\Domain\Payroll\Enums\PayrollAdditionCode;
use App\Domain\Payroll\Models\PayrollPeriod;
use App\Domain\Payroll\Services\CalculateThrService;
use App\Models\Employee;
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
use Illuminate\Database\Eloquent\Builder;

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
                            ->relationship(
                                'employee',
                                'name',
                                modifyQueryUsing: fn(Builder $query) => $query->where('company_id', auth()->user()?->company_id)
                            )
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live(),

                        Select::make('payroll_period_id')
                            ->label('Periode Gaji')
                            ->relationship(
                                'period',
                                'id',
                                modifyQueryUsing: fn(Builder $query) => $query->where('company_id', auth()->user()?->company_id)
                            )
                            ->getOptionLabelFromRecordUsing(fn(PayrollPeriod $record) => $record->period_start->format('M Y') . ' - ' . $record->period_end->format('M Y'))
                            ->searchable()
                            ->preload()
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
                                ->action(function (Set $set, Get $get, CalculateThrService $thrService) {
                                    $employeeId = $get('employee_id');
                                    $periodId = $get('payroll_period_id');
                                    $employeeType = $get('employee_type') ?? 'permanent';
                                    $workingDaysInYear = (int) ($get('working_days_in_year') ?? 260);

                                    if (!$employeeId || !$periodId) {
                                        Notification::make()
                                            ->title('Error')
                                            ->body('Silakan pilih karyawan dan periode gaji terlebih dahulu')
                                            ->danger()
                                            ->send();
                                        return;
                                    }

                                    try {
                                        $employee = Employee::find($employeeId);
                                        $period = PayrollPeriod::find($periodId);
                                        
                                        if (!$employee || !$period) {
                                            throw new \Exception('Karyawan atau periode tidak ditemukan');
                                        }

                                        $result = $thrService->calculate(
                                            $employee,
                                            $period->period_end,
                                            $employeeType,
                                            $workingDaysInYear
                                        );

                                        $set('amount', $result['thr_amount']);
                                        $set('description', $result['calculation_notes']);

                                        Notification::make()
                                            ->title('Berhasil')
                                            ->body("THR berhasil dihitung: Rp " . number_format($result['thr_amount'], 0, ',', '.'))
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
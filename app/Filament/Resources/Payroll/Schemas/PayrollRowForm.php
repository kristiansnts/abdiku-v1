<?php

declare(strict_types=1);

namespace App\Filament\Resources\Payroll\Schemas;

use App\Domain\Payroll\Models\PayrollRow;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;

final class PayrollRowForm
{
    public static function configure(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Informasi Karyawan')
                    ->schema([
                        Placeholder::make('employee.name')
                            ->label('Nama Karyawan')
                            ->content(fn(?PayrollRow $record): string => $record?->employee?->name ?? '-'),

                        Placeholder::make('employee_id')
                            ->label('ID Karyawan')
                            ->content(fn(?PayrollRow $record): string => $record?->employee_id ? '#'.$record->employee_id : '-'),

                        Placeholder::make('attendance_count')
                            ->label('Jumlah Kehadiran')
                            ->content(fn(?PayrollRow $record): string => $record ? (string)$record->attendance_count : '-'),
                    ])
                    ->columns(3),

                Section::make('Ringkasan Gaji')
                    ->schema([
                        Placeholder::make('gross_amount')
                            ->label('Gaji Kotor (Gross)')
                            ->content(fn(?PayrollRow $record): string => $record ? 'Rp ' . number_format((float)$record->gross_amount, 2, ',', '.') : '-'),

                        Placeholder::make('deduction_amount')
                            ->label('Total Potongan')
                            ->content(fn(?PayrollRow $record): string => $record ? 'Rp ' . number_format((float)$record->deduction_amount, 2, ',', '.') : '-'),

                        Placeholder::make('net_amount')
                            ->label('Gaji Bersih (Net)')
                            ->content(fn(?PayrollRow $record): string => $record ? 'Rp ' . number_format((float)$record->net_amount, 2, ',', '.') : '-')
                            ->extraAttributes(['class' => 'font-bold text-lg']),
                    ])
                    ->columns(3),

                Section::make('Detail Kompensasi Karyawan')
                    ->schema([
                        Placeholder::make('base_salary')
                            ->label('Gaji Pokok')
                            ->content(function (?PayrollRow $record): string {
                                if (!$record) {
                                    return '-';
                                }

                                $compensation = $record->employee?->compensations()
                                    ->where('effective_from', '<=', $record->payrollBatch?->payrollPeriod?->period_end ?? now())
                                    ->where(function ($query) use ($record) {
                                        $query->whereNull('effective_to')
                                            ->orWhere('effective_to', '>=', $record->payrollBatch?->payrollPeriod?->period_start ?? now());
                                    })
                                    ->first();

                                return $compensation ? 'Rp ' . number_format((float)$compensation->base_salary, 2, ',', '.') : '-';
                            }),

                        Placeholder::make('total_allowances')
                            ->label('Total Tunjangan')
                            ->content(function (?PayrollRow $record): string {
                                if (!$record) {
                                    return '-';
                                }

                                $compensation = $record->employee?->compensations()
                                    ->where('effective_from', '<=', $record->payrollBatch?->payrollPeriod?->period_end ?? now())
                                    ->where(function ($query) use ($record) {
                                        $query->whereNull('effective_to')
                                            ->orWhere('effective_to', '>=', $record->payrollBatch?->payrollPeriod?->period_start ?? now());
                                    })
                                    ->first();

                                return $compensation ? 'Rp ' . number_format((float)$compensation->total_allowances, 2, ',', '.') : '-';
                            }),
                    ])
                    ->columns(2)
                    ->collapsible(),

                Section::make('Perhitungan Berdasarkan Kehadiran')
                    ->description('Perhitungan gaji berdasarkan jumlah kehadiran dalam periode')
                    ->schema([
                        Placeholder::make('total_working_days')
                            ->label('Total Hari Kerja')
                            ->content(function (?PayrollRow $record): string {
                                if (!$record || !$record->payrollBatch?->payrollPeriod) {
                                    return '-';
                                }

                                $period = $record->payrollBatch->payrollPeriod;
                                $start = $period->period_start;
                                $end = $period->period_end;

                                // Calculate working days (excluding weekends)
                                $totalDays = 0;
                                $currentDate = $start->copy();

                                while ($currentDate <= $end) {
                                    // Count only weekdays (Monday to Friday)
                                    if ($currentDate->isWeekday()) {
                                        $totalDays++;
                                    }
                                    $currentDate->addDay();
                                }

                                return $totalDays . ' hari';
                            }),

                        Placeholder::make('actual_attendance')
                            ->label('Kehadiran Aktual')
                            ->content(fn(?PayrollRow $record): string =>
                                $record ? ($record->attendance_count ?? 0) . ' hari' : '-'
                            ),

                        Placeholder::make('attendance_percentage')
                            ->label('Persentase Kehadiran')
                            ->content(function (?PayrollRow $record): string {
                                if (!$record || !$record->payrollBatch?->payrollPeriod || !$record->attendance_count) {
                                    return '0%';
                                }

                                $period = $record->payrollBatch->payrollPeriod;
                                $start = $period->period_start;
                                $end = $period->period_end;

                                $totalDays = 0;
                                $currentDate = $start->copy();

                                while ($currentDate <= $end) {
                                    if ($currentDate->isWeekday()) {
                                        $totalDays++;
                                    }
                                    $currentDate->addDay();
                                }

                                if ($totalDays === 0) {
                                    return '0%';
                                }

                                $percentage = ($record->attendance_count / $totalDays) * 100;
                                return number_format($percentage, 1) . '%';
                            }),

                        Placeholder::make('prorated_base_salary')
                            ->label('Gaji Pokok (Prorata)')
                            ->content(function (?PayrollRow $record): string {
                                if (!$record || !$record->payrollBatch?->payrollPeriod) {
                                    return '-';
                                }

                                $compensation = $record->employee?->compensations()
                                    ->where('effective_from', '<=', $record->payrollBatch->payrollPeriod->period_end)
                                    ->where(function ($query) use ($record) {
                                        $query->whereNull('effective_to')
                                            ->orWhere('effective_to', '>=', $record->payrollBatch->payrollPeriod->period_start);
                                    })
                                    ->first();

                                if (!$compensation) {
                                    return '-';
                                }

                                $period = $record->payrollBatch->payrollPeriod;
                                $start = $period->period_start;
                                $end = $period->period_end;

                                $totalDays = 0;
                                $currentDate = $start->copy();

                                while ($currentDate <= $end) {
                                    if ($currentDate->isWeekday()) {
                                        $totalDays++;
                                    }
                                    $currentDate->addDay();
                                }

                                if ($totalDays === 0) {
                                    return 'Rp 0';
                                }

                                $baseSalary = (float)$compensation->base_salary;
                                $attendanceCount = $record->attendance_count ?? 0;
                                $proratedSalary = ($attendanceCount / $totalDays) * $baseSalary;

                                return 'Rp ' . number_format($proratedSalary, 2, ',', '.') .
                                       ' (' . $attendanceCount . ' / ' . $totalDays . ' × Rp ' . number_format($baseSalary, 2, ',', '.') . ')';
                            })
                            ->columnSpanFull(),
                    ])
                    ->columns(3)
                    ->collapsible(),

                Section::make('Penambahan (Additions)')
                    ->schema([
                        Repeater::make('additions')
                            ->relationship('additions')
                            ->schema([
                                TextInput::make('addition_code')
                                    ->label('Kode')
                                    ->disabled(),

                                TextInput::make('description')
                                    ->label('Deskripsi')
                                    ->disabled()
                                    ->columnSpan(2),

                                TextInput::make('amount')
                                    ->label('Jumlah')
                                    ->prefix('Rp')
                                    ->disabled()
                                    ->formatStateUsing(fn($state) => number_format((float)$state, 2, ',', '.')),
                            ])
                            ->columns(4)
                            ->disabled()
                            ->addable(false)
                            ->deletable(false)
                            ->reorderable(false)
                            ->defaultItems(0)
                            ->hidden(fn(?PayrollRow $record): bool => !$record || $record->additions->isEmpty()),

                        Placeholder::make('no_additions')
                            ->content('Tidak ada penambahan')
                            ->hidden(fn(?PayrollRow $record): bool => $record && $record->additions->isNotEmpty()),
                    ])
                    ->collapsible(),

                Section::make('Potongan (Deductions)')
                    ->schema([
                        Repeater::make('deductions')
                            ->relationship('deductions')
                            ->schema([
                                TextInput::make('deduction_code')
                                    ->label('Kode')
                                    ->disabled(),

                                TextInput::make('employee_amount')
                                    ->label('Potongan Karyawan')
                                    ->prefix('Rp')
                                    ->disabled()
                                    ->formatStateUsing(fn($state) => number_format((float)$state, 2, ',', '.')),

                                TextInput::make('employer_amount')
                                    ->label('Tanggungan Perusahaan')
                                    ->prefix('Rp')
                                    ->disabled()
                                    ->formatStateUsing(fn($state) => number_format((float)$state, 2, ',', '.')),

                                Placeholder::make('rule_info')
                                    ->label('Info Aturan')
                                    ->content(function ($get): string {
                                        $snapshot = $get('rule_snapshot');
                                        if (is_array($snapshot)) {
                                            return ($snapshot['name'] ?? 'N/A') . ' (' . ($snapshot['code'] ?? 'N/A') . ')';
                                        }
                                        return '-';
                                    }),
                            ])
                            ->columns(4)
                            ->disabled()
                            ->addable(false)
                            ->deletable(false)
                            ->reorderable(false)
                            ->defaultItems(0)
                            ->hidden(fn(?PayrollRow $record): bool => !$record || $record->deductions->isEmpty()),

                        Placeholder::make('no_deductions')
                            ->content('Tidak ada potongan')
                            ->hidden(fn(?PayrollRow $record): bool => $record && $record->deductions->isNotEmpty()),
                    ])
                    ->collapsible(),

                Section::make('Total Potongan')
                    ->schema([
                        Placeholder::make('total_employee_deductions')
                            ->label('Total Potongan Karyawan')
                            ->content(fn(?PayrollRow $record): string =>
                                $record ? 'Rp ' . number_format((float)$record->total_employee_deductions, 2, ',', '.') : '-'
                            ),

                        Placeholder::make('total_employer_deductions')
                            ->label('Total Tanggungan Perusahaan')
                            ->content(fn(?PayrollRow $record): string =>
                                $record ? 'Rp ' . number_format((float)$record->total_employer_deductions, 2, ',', '.') : '-'
                            ),
                    ])
                    ->columns(2)
                    ->collapsible(),
            ]);
    }
}

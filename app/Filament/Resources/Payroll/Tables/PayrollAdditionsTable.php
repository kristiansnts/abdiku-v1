<?php

declare(strict_types=1);

namespace App\Filament\Resources\Payroll\Tables;

use App\Domain\Payroll\Enums\PayrollAdditionCode;
use App\Domain\Payroll\Models\PayrollAddition;
use App\Domain\Payroll\Models\PayrollPeriod;
use App\Domain\Payroll\Services\CalculateThrService;
use App\Models\Employee;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Illuminate\Support\HtmlString;
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
                                    ->options(function () {
                                        return PayrollPeriod::where('company_id', auth()->user()?->company_id)
                                            ->get()
                                            ->mapWithKeys(function ($period) {
                                                return [$period->id => $period->period_start->format('M Y') . ' - ' . $period->period_end->format('M Y')];
                                            });
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
                                    ->content(function (\Filament\Forms\Get $get, CalculateThrService $thrService) {
                                        $periodId = $get('payroll_period_id');
                                        $employeeType = $get('employee_type') ?? 'permanent';
                                        $workingDaysInYear = (int) ($get('working_days_in_year') ?? 260);

                                        if (!$periodId) {
                                            return 'Silakan pilih periode gaji terlebih dahulu.';
                                        }

                                        try {
                                            $period = PayrollPeriod::find($periodId);
                                            if (!$period) {
                                                return 'Periode tidak ditemukan.';
                                            }

                                            // Get all active employees in company
                                            $employees = Employee::where('company_id', auth()->user()?->company_id)
                                                ->where('status', 'ACTIVE')
                                                ->with('compensations')
                                                ->get();

                                            if ($employees->isEmpty()) {
                                                return 'Tidak ada karyawan aktif ditemukan.';
                                            }

                                            $previewData = [];
                                            $totalThr = 0;
                                            $eligibleCount = 0;

                                            foreach ($employees as $employee) {
                                                try {
                                                    // Check if THR already exists
                                                    $existingThr = PayrollAddition::where('employee_id', $employee->id)
                                                        ->where('payroll_period_id', $period->id)
                                                        ->where('code', 'THR')
                                                        ->first();

                                                    if ($existingThr) {
                                                        continue; // Skip if already exists
                                                    }

                                                    $result = $thrService->calculate(
                                                        $employee,
                                                        $period->period_end,
                                                        $employeeType,
                                                        $workingDaysInYear
                                                    );

                                                    if ($result['thr_amount'] > 0) {
                                                        $previewData[] = [
                                                            'name' => $employee->name,
                                                            'amount' => $result['thr_amount'],
                                                            'notes' => $result['calculation_notes'],
                                                            'months_worked' => $result['months_worked'],
                                                        ];
                                                        $totalThr += $result['thr_amount'];
                                                        $eligibleCount++;
                                                    }

                                                } catch (\Exception $e) {
                                                    $previewData[] = [
                                                        'name' => $employee->name,
                                                        'amount' => 0,
                                                        'notes' => 'Error: ' . $e->getMessage(),
                                                        'months_worked' => 0,
                                                    ];
                                                }
                                            }

                                            if (empty($previewData)) {
                                                return 'Tidak ada karyawan yang memenuhi syarat THR atau semua sudah memiliki THR untuk periode ini.';
                                            }

                                            // Generate HTML table
                                            $html = '<div class="space-y-4">';
                                            $html .= '<div class="bg-green-50 p-4 rounded-lg border border-green-200">';
                                            $html .= '<h4 class="font-semibold text-green-800 mb-2">Ringkasan</h4>';
                                            $html .= '<div class="grid grid-cols-2 gap-4 text-sm">';
                                            $html .= '<div>Jumlah Karyawan: ' . $eligibleCount . '</div>';
                                            $html .= '<div>Total THR: Rp ' . number_format($totalThr, 0, ',', '.') . '</div>';
                                            $html .= '</div>';
                                            $html .= '</div>';

                                            $html .= '<div class="overflow-x-auto">';
                                            $html .= '<table class="w-full text-sm border border-gray-200">';
                                            $html .= '<thead class="bg-gray-50">';
                                            $html .= '<tr>';
                                            $html .= '<th class="p-2 text-left border border-gray-200">Karyawan</th>';
                                            $html .= '<th class="p-2 text-right border border-gray-200">Masa Kerja (bulan)</th>';
                                            $html .= '<th class="p-2 text-right border border-gray-200">Jumlah THR</th>';
                                            $html .= '<th class="p-2 text-left border border-gray-200">Keterangan</th>';
                                            $html .= '</tr>';
                                            $html .= '</thead>';
                                            $html .= '<tbody>';

                                            foreach ($previewData as $data) {
                                                $html .= '<tr class="hover:bg-gray-50">';
                                                $html .= '<td class="p-2 border border-gray-200 font-medium">' . $data['name'] . '</td>';
                                                $html .= '<td class="p-2 border border-gray-200 text-right">' . $data['months_worked'] . '</td>';
                                                $html .= '<td class="p-2 border border-gray-200 text-right font-mono">';
                                                if ($data['amount'] > 0) {
                                                    $html .= 'Rp ' . number_format($data['amount'], 0, ',', '.');
                                                } else {
                                                    $html .= '-';
                                                }
                                                $html .= '</td>';
                                                $html .= '<td class="p-2 border border-gray-200 text-xs text-gray-600">' . $data['notes'] . '</td>';
                                                $html .= '</tr>';
                                            }

                                            $html .= '</tbody>';
                                            $html .= '</table>';
                                            $html .= '</div>';
                                            $html .= '</div>';

                                            return new HtmlString($html);

                                        } catch (\Exception $e) {
                                            return 'Error saat menghasilkan preview: ' . $e->getMessage();
                                        }
                                    })
                                    ->columnSpanFull(),
                            ]),
                    ])
                    ->action(function (array $data, CalculateThrService $thrService) {
                        try {
                            $period = PayrollPeriod::find($data['payroll_period_id']);
                            $employeeType = $data['employee_type'];
                            $workingDaysInYear = (int) ($data['working_days_in_year'] ?? 260);

                            if (!$period) {
                                throw new \Exception('Periode tidak ditemukan');
                            }

                            // Get all active employees in company
                            $employees = Employee::where('company_id', auth()->user()?->company_id)
                                ->where('status', 'ACTIVE')
                                ->get();

                            $successCount = 0;
                            $errors = [];

                            /** @var Employee $employee */
                            foreach ($employees as $employee) {
                                try {
                                    // Check if THR already exists for this employee and period
                                    $existingThr = PayrollAddition::where('employee_id', $employee->id)
                                        ->where('payroll_period_id', $period->id)
                                        ->where('code', 'THR')
                                        ->first();

                                    if ($existingThr) {
                                        continue; // Skip if THR already exists
                                    }

                                    $result = $thrService->calculate(
                                        $employee,
                                        $period->period_end,
                                        $employeeType,
                                        $workingDaysInYear
                                    );

                                    if ($result['thr_amount'] > 0) {
                                        PayrollAddition::create([
                                            'employee_id' => $employee->id,
                                            'payroll_period_id' => $period->id,
                                            'code' => 'THR',
                                            'amount' => $result['thr_amount'],
                                            'description' => $result['calculation_notes'],
                                            'created_by' => auth()->id(),
                                        ]);

                                        $successCount++;
                                    }
                                } catch (\Exception $e) {
                                    $errors[] = $employee->name . ': ' . $e->getMessage();
                                }
                            }

                            if ($successCount > 0) {
                                Notification::make()
                                    ->title('Berhasil')
                                    ->body("THR berhasil dihitung untuk {$successCount} karyawan")
                                    ->success()
                                    ->send();
                            }

                            if (!empty($errors)) {
                                Notification::make()
                                    ->title('Ada Error')
                                    ->body('Beberapa perhitungan gagal: ' . implode(', ', \array_slice($errors, 0, 3)))
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
<?php

declare(strict_types=1);

namespace App\Filament\Resources\CompensationRules\Schemas;

use App\Domain\Payroll\Enums\DeductionBasisType;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;

final class CompensationRuleForm
{
    public static function configure(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Informasi Dasar')
                    ->schema([
                        TextInput::make('code')
                            ->label('Kode')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(50)
                            ->helperText('Kode unik untuk aturan ini (contoh: BPJS_TK, BPJS_KES, JPK)'),

                        TextInput::make('name')
                            ->label('Nama Aturan')
                            ->required()
                            ->maxLength(255)
                            ->helperText('Nama lengkap aturan kompensasi'),

                        Select::make('basis_type')
                            ->label('Dasar Perhitungan')
                            ->options(DeductionBasisType::class)
                            ->required()
                            ->native(false)
                            ->helperText('Pilih dasar perhitungan potongan'),
                    ])
                    ->columns(3),

                Section::make('Persentase Potongan')
                    ->schema([
                        TextInput::make('employee_rate')
                            ->label('Persentase Karyawan (%)')
                            ->numeric()
                            ->suffix('%')
                            ->minValue(0)
                            ->maxValue(100)
                            ->step(0.01)
                            ->nullable()
                            ->helperText('Persentase yang dipotong dari karyawan'),

                        TextInput::make('employer_rate')
                            ->label('Persentase Perusahaan (%)')
                            ->numeric()
                            ->suffix('%')
                            ->minValue(0)
                            ->maxValue(100)
                            ->step(0.01)
                            ->nullable()
                            ->helperText('Persentase yang ditanggung perusahaan'),

                        TextInput::make('salary_cap')
                            ->label('Batas Gaji Maksimal')
                            ->numeric()
                            ->prefix('Rp')
                            ->nullable()
                            ->minValue(0)
                            ->maxValue(999999999)
                            ->helperText('Batas maksimal gaji untuk perhitungan (kosongkan jika tidak ada)'),
                    ])
                    ->columns(3),

                Section::make('Periode Berlaku')
                    ->schema([
                        DatePicker::make('effective_from')
                            ->label('Berlaku Dari')
                            ->required()
                            ->default(now())
                            ->native(false)
                            ->helperText('Tanggal mulai berlaku'),

                        DatePicker::make('effective_to')
                            ->label('Berlaku Sampai')
                            ->nullable()
                            ->native(false)
                            ->helperText('Kosongkan jika masih aktif'),
                    ])
                    ->columns(2),

                Section::make('Catatan Tambahan')
                    ->schema([
                        Textarea::make('notes')
                            ->label('Catatan')
                            ->maxLength(1000)
                            ->rows(3)
                            ->helperText('Catatan tambahan tentang aturan ini'),
                    ])
                    ->columns(1)
                    ->collapsible(),
            ]);
    }
}

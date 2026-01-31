<?php

declare(strict_types=1);

namespace App\Filament\Resources\EmployeeCompensations\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;

final class EmployeeCompensationForm
{
    public static function configure(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Informasi Karyawan')
                    ->schema([
                        Select::make('employee_id')
                            ->label('Karyawan')
                            ->relationship(
                                'employee',
                                'name',
                                fn($query) => $query->where('company_id', auth()->user()?->company_id)
                                    ->where('status', 'ACTIVE')
                            )
                            ->searchable()
                            ->preload()
                            ->required()
                            ->native(false),
                    ])
                    ->columns(1),

                Section::make('Detail Kompensasi')
                    ->schema([
                        TextInput::make('base_salary')
                            ->label('Gaji Pokok')
                            ->numeric()
                            ->prefix('Rp')
                            ->required()
                            ->minValue(0)
                            ->maxValue(999999999)
                            ->helperText('Gaji pokok sebelum tunjangan'),

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
                    ->columns(3),

                Section::make('Tunjangan')
                    ->schema([
                        KeyValue::make('allowances')
                            ->label('Daftar Tunjangan')
                            ->keyLabel('Jenis Tunjangan')
                            ->valueLabel('Jumlah (Rp)')
                            ->addActionLabel('Tambah Tunjangan')
                            ->helperText('Contoh: transport, meal, communication, dll.')
                            ->default([]),
                    ])
                    ->columns(1),

                Section::make('Catatan Tambahan')
                    ->schema([
                        Textarea::make('notes')
                            ->label('Catatan')
                            ->maxLength(1000)
                            ->rows(3)
                            ->helperText('Catatan tambahan tentang kompensasi ini'),
                    ])
                    ->columns(1)
                    ->collapsible(),
            ]);
    }
}

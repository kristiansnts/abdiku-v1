<?php

declare(strict_types=1);

namespace App\Filament\Resources\Employees\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;

final class EmployeeForm
{
    public static function configure(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->label('Nama Lengkap')
                    ->maxLength(255)
                    ->required(),

                TextInput::make('employee_id')
                    ->label('ID Karyawan')
                    ->maxLength(50)
                    ->nullable()
                    ->unique('employees', 'employee_id', ignoreRecord: true)
                    ->helperText('Nomor identifikasi karyawan (opsional)'),

                Select::make('company_id')
                    ->label('Perusahaan')
                    ->relationship('company', 'name')
                    ->required()
                    ->default(fn() => auth()->user()?->company_id)
                    ->disabled(fn() => !auth()->user()?->hasRole('owner'))
                    ->native(false),

                TextInput::make('email')
                    ->label('Email')
                    ->email()
                    ->required()
                    ->maxLength(255)
                    ->unique('users', 'email', modifyRuleUsing: function ($rule, $get) {
                        return $rule->where('company_id', $get('company_id') ?? auth()->user()?->company_id);
                    })
                    ->dehydrated(false)
                    ->helperText('Digunakan untuk login dan pengiriman undangan'),

                TextInput::make('phone')
                    ->label('Nomor HP')
                    ->tel()
                    ->nullable()
                    ->maxLength(20),

                Select::make('department_id')
                    ->label('Departemen')
                    ->relationship(
                        'department',
                        'name',
                        fn($query) => $query->where('company_id', auth()->user()?->company_id)
                    )
                    ->nullable()
                    ->searchable()
                    ->preload()
                    ->native(false)
                    ->helperText('Opsional'),

                Select::make('status')
                    ->label('Status')
                    ->options([
                        'ACTIVE' => 'Aktif',
                        'INACTIVE' => 'Tidak Aktif',
                        'RESIGNED' => 'Mengundurkan Diri',
                    ])
                    ->default('ACTIVE')
                    ->required()
                    ->native(false),

                DatePicker::make('join_date')
                    ->label('Tanggal Bergabung')
                    ->required()
                    ->default(now())
                    ->native(false),

                DatePicker::make('resign_date')
                    ->label('Tanggal Resign')
                    ->nullable()
                    ->native(false)
                    ->helperText('Kosongkan jika masih aktif'),

                Section::make('Informasi Legal & Pajak')
                    ->description('Data mandatory untuk perhitungan PPh21 dan BPJS')
                    ->schema([
                        Select::make('ptkp_status')
                            ->label('Status PTKP')
                            ->options([
                                'TK/0' => 'TK/0 (Tidak Kawin, 0 Tanggungan)',
                                'TK/1' => 'TK/1 (Tidak Kawin, 1 Tanggungan)',
                                'TK/2' => 'TK/2 (Tidak Kawin, 2 Tanggungan)',
                                'TK/3' => 'TK/3 (Tidak Kawin, 3 Tanggungan)',
                                'K/0'  => 'K/0 (Kawin, 0 Tanggungan)',
                                'K/1'  => 'K/1 (Kawin, 1 Tanggungan)',
                                'K/2'  => 'K/2 (Kawin, 2 Tanggungan)',
                                'K/3'  => 'K/3 (Kawin, 3 Tanggungan)',
                            ])
                            ->required()
                            ->native(false),
                        
                        TextInput::make('npwp')
                            ->label('Nomor NPWP')
                            ->maxLength(20),

                        TextInput::make('nik')
                            ->label('Nomor NIK (KTP)')
                            ->maxLength(20),

                        TextInput::make('bpjs_kesehatan_number')
                            ->label('No. BPJS Kesehatan')
                            ->maxLength(30),

                        TextInput::make('bpjs_ketenagakerjaan_number')
                            ->label('No. BPJS Ketenagakerjaan')
                            ->maxLength(30),
                    ])->columns(2),

                Section::make('Pengaturan Akun')
                    ->description('Kelola peran dan hak akses pengguna')
                    ->schema([
                        Select::make('user_role')
                            ->label('Peran Pengguna')
                            ->options([
                                'employee' => 'Karyawan',
                                'hr' => 'HR',
                                'owner' => 'Pemilik',
                            ])
                            ->native(false)
                            ->required()
                            ->default(fn($record) => $record?->user?->roles?->first()?->name ?? 'employee')
                            ->helperText('Peran menentukan hak akses pengguna dalam sistem')
                            ->dehydrated(false)
                            ->visible(
                                fn(Get $get, $record) =>
                                auth()->user()?->hasRole('owner') &&
                                $record?->user_id !== null
                            ),
                    ])
                    ->visible(
                        fn(Get $get, $record) =>
                        auth()->user()?->hasRole('owner') &&
                        $record?->user_id !== null
                    )
                    ->collapsible(),
            ]);
    }
}

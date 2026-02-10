<?php

declare(strict_types=1);

namespace App\Filament\Resources\Employees\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;

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
                    ->unique('users', 'email')
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
            ]);
    }
}

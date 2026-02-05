<?php

declare(strict_types=1);

namespace App\Filament\Resources\Companies\Schemas;

use App\Filament\Forms\Components\LocationMapPicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Form;
use Filament\Forms\Get;

final class CompanyForm
{
    public static function configure(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Informasi Perusahaan')
                    ->schema([
                        TextInput::make('name')
                            ->label('Nama Perusahaan')
                            ->maxLength(255)
                            ->required(),
                    ]),

                Section::make('Lokasi Kantor')
                    ->description('Kelola lokasi kantor untuk presensi geofence')
                    ->schema([
                        Repeater::make('locations')
                            ->relationship()
                            ->label('')
                            ->schema([
                                TextInput::make('name')
                                    ->label('Nama Lokasi')
                                    ->placeholder('Contoh: Kantor Pusat Jakarta')
                                    ->maxLength(255)
                                    ->required()
                                    ->columnSpanFull(),

                                TextInput::make('address')
                                    ->label('Alamat')
                                    ->maxLength(500)
                                    ->columnSpanFull(),

                                LocationMapPicker::make('location_data')
                                    ->label('Pilih Lokasi di Peta')
                                    ->latitude(fn (Get $get) => $get('latitude'))
                                    ->longitude(fn (Get $get) => $get('longitude'))
                                    ->radius(fn (Get $get) => $get('geofence_radius_meters'))
                                    ->address(fn (Get $get) => $get('address'))
                                    ->dehydrated(false)
                                    ->columnSpanFull(),

                                Hidden::make('latitude')
                                    ->default(-6.2297),

                                Hidden::make('longitude')
                                    ->default(106.8164),

                                Hidden::make('geofence_radius_meters')
                                    ->default(100),

                                Toggle::make('is_default')
                                    ->label('Lokasi Utama')
                                    ->helperText('Tandai sebagai lokasi utama perusahaan')
                                    ->default(false),
                            ])
                            ->columns(1)
                            ->itemLabel(fn(array $state): ?string => $state['name'] ?? 'Lokasi Baru')
                            ->addActionLabel('Tambah Lokasi')
                            ->reorderable()
                            ->collapsible()
                            ->defaultItems(0),
                    ]),
            ]);
    }
}

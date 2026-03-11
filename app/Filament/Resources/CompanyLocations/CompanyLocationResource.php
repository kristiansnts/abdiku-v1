<?php

declare(strict_types=1);

namespace App\Filament\Resources\CompanyLocations;

use App\Filament\Forms\Components\LocationMapPicker;
use App\Filament\Resources\CompanyLocations\Pages\CreateCompanyLocation;
use App\Filament\Resources\CompanyLocations\Pages\EditCompanyLocation;
use App\Filament\Resources\CompanyLocations\Pages\ListCompanyLocations;
use App\Models\CompanyLocation;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

final class CompanyLocationResource extends Resource
{
    protected static ?string $model = CompanyLocation::class;

    protected static ?string $navigationIcon = 'heroicon-o-map-pin';

    protected static ?string $navigationGroup = 'Pengaturan';

    protected static ?string $modelLabel = 'Lokasi Kantor';

    protected static ?string $pluralModelLabel = 'Lokasi Kantor';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $slug = 'company-locations';

    protected static ?int $navigationSort = 3;

    public static function shouldRegisterNavigation(): bool
    {
        // Only accessible via Company Settings button, not in sidebar
        return false;
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();
        return $user?->company_id !== null
            && !$user->hasRole(['super_admin', 'super-admin']);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('company_id', auth()->user()?->company_id);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Informasi Lokasi')
                ->schema([
                    TextInput::make('name')
                        ->label('Nama Lokasi')
                        ->placeholder('Contoh: Kantor Cabang Surabaya')
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull(),

                    TextInput::make('address')
                        ->label('Alamat')
                        ->maxLength(500)
                        ->live()
                        ->columnSpanFull(),

                    Toggle::make('is_default')
                        ->label('Jadikan Lokasi Utama')
                        ->helperText('Lokasi utama digunakan sebagai default untuk presensi karyawan')
                        ->default(false),
                ]),

            Section::make('Titik Koordinat & Radius Geofence')
                ->description('Tentukan titik koordinat kantor dan radius area presensi yang diizinkan')
                ->schema([
                    LocationMapPicker::make('location_data')
                        ->label('Pilih Lokasi di Peta')
                        ->latitude(fn(Get $get) => $get('latitude'))
                        ->longitude(fn(Get $get) => $get('longitude'))
                        ->radius(fn(Get $get) => $get('geofence_radius_meters'))
                        ->address(fn(Get $get) => $get('address'))
                        ->dehydrated(false)
                        ->columnSpanFull(),

                    Hidden::make('latitude')->default(-6.2297),
                    Hidden::make('longitude')->default(106.8164),
                    Hidden::make('geofence_radius_meters')->default(100),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nama Lokasi')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('address')
                    ->label('Alamat')
                    ->limit(45)
                    ->placeholder('—')
                    ->toggleable(),

                TextColumn::make('geofence_radius_meters')
                    ->label('Radius')
                    ->suffix(' m')
                    ->sortable(),

                IconColumn::make('is_default')
                    ->label('Utama')
                    ->boolean()
                    ->trueIcon('heroicon-o-star')
                    ->falseIcon('heroicon-o-minus')
                    ->trueColor('warning'),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make()
                    ->before(function (Model $record, DeleteAction $action) {
                        $count = CompanyLocation::where('company_id', $record->company_id)->count();
                        if ($count <= 1) {
                            $action->cancel();
                            \Filament\Notifications\Notification::make()
                                ->title('Tidak dapat dihapus')
                                ->body('Minimal harus ada satu lokasi kantor.')
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->emptyStateIcon('heroicon-o-map-pin')
            ->emptyStateHeading('Belum ada lokasi')
            ->emptyStateDescription('Tambah lokasi kantor untuk mengaktifkan presensi geofence.')
            ->defaultSort('is_default', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListCompanyLocations::route('/'),
            'create' => CreateCompanyLocation::route('/create'),
            'edit'   => EditCompanyLocation::route('/{record}/edit'),
        ];
    }
}

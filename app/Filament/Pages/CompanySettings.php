<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Filament\Forms\Components\LocationMapPicker;
use App\Models\Department;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables\Actions\CreateAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Arr;

class CompanySettings extends Page implements HasForms, HasTable
{
    use InteractsWithForms, InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-building-office';

    protected static ?string $navigationGroup = 'Pengaturan';

    protected static ?string $navigationLabel = 'Pengaturan Perusahaan';

    protected static ?string $title = 'Pengaturan Perusahaan';

    protected static ?int $navigationSort = 1;

    protected static string $view = 'filament.pages.company-settings';

    public ?array $data = [];

    public static function canAccess(): bool
    {
        return auth()->check();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return !auth()->user()?->hasRole('super_admin');
    }

    public function mount(): void
    {
        $company = auth()->user()->company;

        $this->form->fill([
            'name' => $company->name,
            'locations' => $company->locations->map(fn($l) => [
                'id' => $l->id,
                'name' => $l->name,
                'address' => $l->address,
                'latitude' => $l->latitude,
                'longitude' => $l->longitude,
                'geofence_radius_meters' => $l->geofence_radius_meters,
                'is_default' => $l->is_default,
            ])->toArray(),
        ]);
    }

    public function form(Form $form): Form
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
                                    ->latitude(fn(Get $get) => $get('latitude'))
                                    ->longitude(fn(Get $get) => $get('longitude'))
                                    ->radius(fn(Get $get) => $get('geofence_radius_meters'))
                                    ->address(fn(Get $get) => $get('address'))
                                    ->dehydrated(false)
                                    ->columnSpanFull(),

                                Hidden::make('id'),
                                Hidden::make('latitude')->default(-6.2297),
                                Hidden::make('longitude')->default(106.8164),
                                Hidden::make('geofence_radius_meters')->default(100),

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
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();
        $company = auth()->user()->company;

        $company->update(['name' => $data['name']]);

        $submittedIds = collect($data['locations'])->pluck('id')->filter()->values()->toArray();
        $company->locations()->whereNotIn('id', $submittedIds)->delete();

        foreach ($data['locations'] as $location) {
            $locationData = Arr::except($location, ['id', 'location_data']);
            if (!empty($location['id'])) {
                $company->locations()->where('id', $location['id'])->update($locationData);
            } else {
                $company->locations()->create($locationData);
            }
        }

        Notification::make()
            ->title('Pengaturan perusahaan berhasil disimpan')
            ->success()
            ->send();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Department::query()
                    ->where('company_id', auth()->user()?->company_id)
            )
            ->columns([
                TextColumn::make('name')
                    ->label('Nama Departemen')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('description')
                    ->label('Deskripsi')
                    ->limit(50)
                    ->placeholder('—'),

                TextColumn::make('employees_count')
                    ->label('Jumlah Karyawan')
                    ->counts('employees')
                    ->sortable(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Tambah Departemen')
                    ->model(Department::class)
                    ->form([
                        TextInput::make('name')
                            ->label('Nama Departemen')
                            ->required()
                            ->maxLength(255),
                        Textarea::make('description')
                            ->label('Deskripsi')
                            ->nullable()
                            ->rows(3),
                    ])
                    ->mutateFormDataUsing(fn(array $data): array => array_merge($data, [
                        'company_id' => auth()->user()?->company_id,
                    ])),
            ])
            ->actions([
                EditAction::make()
                    ->form([
                        TextInput::make('name')
                            ->label('Nama Departemen')
                            ->required()
                            ->maxLength(255),
                        Textarea::make('description')
                            ->label('Deskripsi')
                            ->nullable()
                            ->rows(3),
                    ]),
                DeleteAction::make(),
            ])
            ->emptyStateHeading('Belum ada departemen')
            ->emptyStateDescription('Tambah departemen untuk mengorganisir karyawan.');
    }
}

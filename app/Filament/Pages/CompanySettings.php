<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Filament\Forms\Components\LocationMapPicker;
use App\Models\Company;
use App\Models\Department;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
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
        // Super admins don't have a company, so they shouldn't access this page
        if (auth()->user()?->hasRole(['super_admin', 'super-admin'])) {
            return false;
        }

        // Allow owners and other roles to access (owners might not have a company yet)
        return auth()->check();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return !auth()->user()?->hasRole(['super_admin', 'super-admin']);
    }

    public function mount(): void
    {
        $company = auth()->user()->company;

        // If user doesn't have a company, show empty form for initial setup
        if (!$company) {
            $this->form->fill([
                'name' => '',
                'location_name' => '',
                'address' => '',
                'latitude' => -6.2297,
                'longitude' => 106.8164,
                'geofence_radius_meters' => 100,
            ]);
            return;
        }

        // Load existing company data
        $location = $company->locations()->first();
        $this->form->fill([
            'name' => $company->name,
            'location_name' => $location?->name ?? '',
            'address' => $location?->address ?? '',
            'latitude' => $location?->latitude ?? -6.2297,
            'longitude' => $location?->longitude ?? 106.8164,
            'geofence_radius_meters' => $location?->geofence_radius_meters ?? 100,
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
                    ->description('Atur lokasi kantor untuk presensi geofence')
                    ->schema([
                        TextInput::make('location_name')
                            ->label('Nama Lokasi')
                            ->placeholder('Contoh: Kantor Pusat Jakarta')
                            ->maxLength(255)
                            ->required()
                            ->columnSpanFull(),

                        TextInput::make('address')
                            ->label('Alamat')
                            ->maxLength(500)
                            ->reactive()
                            ->visible(fn(Get $get) => filled($get('location_name')))
                            ->live()
                            ->columnSpanFull(),

                        LocationMapPicker::make('location_data')
                            ->label('Pilih Lokasi di Peta')
                            ->visible(fn(Get $get) => filled($get('location_name')))
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
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();
        $user = auth()->user();
        $company = $user->company;

        // If user doesn't have a company, create one
        if (!$company) {
            $company = Company::create([
                'name' => $data['name'],
            ]);

            // Associate the user with the new company
            $user->update(['company_id' => $company->id]);

            Notification::make()
                ->title('Perusahaan berhasil dibuat')
                ->body('Selamat datang! Perusahaan Anda telah berhasil dibuat.')
                ->success()
                ->send();
        } else {
            // Update existing company
            $company->update(['name' => $data['name']]);
        }

        // Handle single location
        $location = $company->locations()->first();
        $locationData = [
            'name' => $data['location_name'],
            'address' => $data['address'],
            'latitude' => $data['latitude'],
            'longitude' => $data['longitude'],
            'geofence_radius_meters' => $data['geofence_radius_meters'],
            'is_default' => true, // Always default since there's only one
        ];

        if ($location) {
            // Update existing location
            $location->update($locationData);
        } else {
            // Create new location
            $company->locations()->create($locationData);
        }

        Notification::make()
            ->title('Pengaturan perusahaan berhasil disimpan')
            ->success()
            ->send();

        redirect()->to('/admin/company-settings');
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

<?php

namespace App\Filament\Pages;

use App\Filament\Forms\Components\LocationMapPicker;
use App\Models\Company;
use App\Domain\Attendance\Models\ShiftPolicy;
use App\Domain\Attendance\Models\WorkPattern;
use App\Domain\Leave\Services\IndonesiaHolidayService;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Components\Wizard\Step;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class OnboardingWizard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-rocket-launch';
    protected static string $view = 'filament.pages.onboarding-wizard';
    protected static bool $shouldRegisterNavigation = false;

    public ?array $data = [];

    public function mount(): void
    {
        $company = Auth::user()->company;

        if ($company->is_onboarded) {
            redirect('/admin');
        }

        $location = $company->locations()->first();

        $this->form->fill([
            'company_name' => $company->name,
            'location_name' => $location?->name ?? '',
            'address' => $location?->address ?? '',
            'latitude' => $location?->latitude ?? -6.2297,
            'longitude' => $location?->longitude ?? 106.8164,
            'geofence_radius_meters' => $location?->geofence_radius_meters ?? 100,
            'npwp' => $company->npwp,
            'shift_name' => 'Shift Reguler',
            'start_time' => '08:00',
            'end_time' => '17:00',
            'work_days' => [1, 2, 3, 4, 5],
            'auto_sync_holidays' => true,
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Wizard::make([
                    Step::make('Profil Perusahaan')
                        ->description('Lengkapi data dasar bisnis Anda')
                        ->schema([
                            TextInput::make('company_name')
                                ->required()
                                ->label('Nama Perusahaan'),
                            TextInput::make('npwp')
                                ->label('NPWP Perusahaan (Opsional)'),
                        ]),

                    Step::make('Nama Lokasi Kantor')
                        ->description('Tentukan nama lokasi kantor Anda')
                        ->schema([
                            TextInput::make('location_name')
                                ->required()
                                ->label('Nama Lokasi')
                                ->placeholder('Contoh: Kantor Pusat Jakarta')
                                ->helperText('Masukkan nama lokasi kantor Anda'),
                        ]),

                    Step::make('Alamat & Peta Lokasi')
                        ->description('Tentukan alamat dan posisi kantor di peta')
                        ->schema([
                            TextInput::make('address')
                                ->required()
                                ->label('Alamat Lengkap')
                                ->reactive()
                                ->live()
                                ->columnSpanFull(),

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

                    Step::make('Aturan Jam Kerja')
                        ->description('Tentukan jam masuk dan pulang default')
                        ->schema([
                            TextInput::make('shift_name')->required()->label('Nama Shift'),
                            TimePicker::make('start_time')->required()->label('Jam Masuk'),
                            TimePicker::make('end_time')->required()->label('Jam Pulang'),
                        ]),

                    Step::make('Pola Kerja')
                        ->description('Hari apa saja karyawan Anda bekerja?')
                        ->schema([
                            Select::make('work_days')
                                ->multiple()
                                ->options([
                                    1 => 'Senin',
                                    2 => 'Selasa',
                                    3 => 'Rabu',
                                    4 => 'Kamis',
                                    5 => 'Jumat',
                                    6 => 'Sabtu',
                                    7 => 'Minggu',
                                ])
                                ->required()
                                ->label('Hari Kerja Efektif'),
                        ]),

                    Step::make('Otomatisasi Libur')
                        ->description('Gunakan kalender libur nasional otomatis')
                        ->schema([
                            Toggle::make('auto_sync_holidays')
                                ->label('Aktifkan Auto-Sync Hari Libur Nasional (Indonesia)')
                                ->helperText('Jika aktif, kalender Anda akan otomatis terisi Hari Libur Nasional dari Pemerintah.')
                                ->default(true),
                        ]),
                ])
                ->submitAction(view('filament.pages.onboarding-submit-button'))
            ])
            ->statePath('data');
    }

    public function submit(): void
    {
        $state = $this->form->getState();
        $company = Auth::user()->company;

        DB::transaction(function () use ($state, $company) {
            // 1. Update Company
            $company->update([
                'name' => $state['company_name'],
                'npwp' => $state['npwp'],
                'auto_sync_holidays' => $state['auto_sync_holidays'],
                'is_onboarded' => true,
            ]);

            // 2. Create or Update Location
            $location = $company->locations()->first();
            $locationData = [
                'name' => $state['location_name'],
                'address' => $state['address'],
                'latitude' => $state['latitude'],
                'longitude' => $state['longitude'],
                'geofence_radius_meters' => $state['geofence_radius_meters'],
                'is_default' => true,
            ];

            if ($location) {
                $location->update($locationData);
            } else {
                $company->locations()->create($locationData);
            }

            // 3. Create Default Shift
            ShiftPolicy::create([
                'company_id' => $company->id,
                'name' => $state['shift_name'],
                'start_time' => $state['start_time'],
                'end_time' => $state['end_time'],
                'late_after_minutes' => 15,
            ]);

            // 4. Create Work Pattern
            WorkPattern::create([
                'company_id' => $company->id,
                'name' => 'Pola Kerja Default',
                'working_days' => $state['work_days'],
            ]);

            // 5. Initial Holiday Sync if enabled
            if ($state['auto_sync_holidays']) {
                $service = app(IndonesiaHolidayService::class);
                $service->distributeToTenants();
            }
        });

        $this->redirect('/admin');
    }
}

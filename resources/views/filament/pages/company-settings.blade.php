<x-filament-panels::page>
    <x-filament::section>
        <x-slot name="heading">Informasi Perusahaan</x-slot>
        <form wire:submit="save">
            {{ $this->form }}
            <div class="mt-6">
                <x-filament::button type="submit">
                    Simpan Pengaturan
                </x-filament::button>
            </div>
        </form>
        <x-filament-actions::modals />
    </x-filament::section>

    <x-filament::section>
        <x-slot name="heading">Lokasi Kantor</x-slot>
        <x-slot name="description">Kelola semua lokasi kantor untuk presensi geofence karyawan</x-slot>
        <div class="flex items-center justify-between">
            <p class="text-sm text-gray-500">
                {{ auth()->user()?->company?->locations()->count() ?? 0 }} lokasi terdaftar
            </p>
            <x-filament::button
                href="{{ route('filament.admin.resources.company-locations.index') }}"
                tag="a"
                color="gray"
                icon="heroicon-m-map-pin"
            >
                Kelola Lokasi Kantor
            </x-filament::button>
        </div>
    </x-filament::section>

    <x-filament::section>
        <x-slot name="heading">Departemen</x-slot>
        {{ $this->table }}
    </x-filament::section>
</x-filament-panels::page>

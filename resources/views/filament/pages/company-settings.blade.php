<x-filament-panels::page>
    <x-filament::section>
        <x-slot name="heading">Informasi Perusahaan & Lokasi</x-slot>
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
        <x-slot name="heading">Departemen</x-slot>
        {{ $this->table }}
    </x-filament::section>
</x-filament-panels::page>

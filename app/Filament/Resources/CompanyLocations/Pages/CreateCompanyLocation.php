<?php

declare(strict_types=1);

namespace App\Filament\Resources\CompanyLocations\Pages;

use App\Filament\Resources\CompanyLocations\CompanyLocationResource;
use App\Models\CompanyLocation;
use Filament\Resources\Pages\CreateRecord;

class CreateCompanyLocation extends CreateRecord
{
    protected static string $resource = CompanyLocationResource::class;

    protected static ?string $title = 'Tambah Lokasi Kantor';

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['company_id'] = auth()->user()->company_id;
        return $data;
    }

    protected function afterCreate(): void
    {
        // If this new location is set as default, unset all others
        if ($this->record->is_default) {
            CompanyLocation::where('company_id', $this->record->company_id)
                ->where('id', '!=', $this->record->id)
                ->update(['is_default' => false]);
        }

        // If this is the first location, auto-set as default
        $count = CompanyLocation::where('company_id', $this->record->company_id)->count();
        if ($count === 1) {
            $this->record->update(['is_default' => true]);
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}

<?php

declare(strict_types=1);

namespace App\Filament\Resources\CompanyLocations\Pages;

use App\Filament\Resources\CompanyLocations\CompanyLocationResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCompanyLocations extends ListRecords
{
    protected static string $resource = CompanyLocationResource::class;

    protected static ?string $title = 'Lokasi Kantor';

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Tambah Lokasi'),
        ];
    }
}

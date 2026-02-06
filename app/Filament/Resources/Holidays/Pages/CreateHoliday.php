<?php

declare(strict_types=1);

namespace App\Filament\Resources\Holidays\Pages;

use App\Filament\Resources\Holidays\HolidayResource;
use Filament\Resources\Pages\CreateRecord;

class CreateHoliday extends CreateRecord
{
    protected static string $resource = HolidayResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['company_id'] = auth()->user()?->company_id;

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}

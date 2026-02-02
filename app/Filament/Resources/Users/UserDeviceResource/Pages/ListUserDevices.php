<?php

declare(strict_types=1);

namespace App\Filament\Resources\Users\UserDeviceResource\Pages;

use App\Filament\Resources\Users\UserDeviceResource;
use Filament\Resources\Pages\ListRecords;

class ListUserDevices extends ListRecords
{
    protected static string $resource = UserDeviceResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}

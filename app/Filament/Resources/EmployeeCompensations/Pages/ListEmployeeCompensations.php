<?php

declare(strict_types=1);

namespace App\Filament\Resources\EmployeeCompensations\Pages;

use App\Filament\Resources\EmployeeCompensations\EmployeeCompensationResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

final class ListEmployeeCompensations extends ListRecords
{
    protected static string $resource = EmployeeCompensationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

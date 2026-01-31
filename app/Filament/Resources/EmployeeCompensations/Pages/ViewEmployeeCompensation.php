<?php

declare(strict_types=1);

namespace App\Filament\Resources\EmployeeCompensations\Pages;

use App\Filament\Resources\EmployeeCompensations\EmployeeCompensationResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

final class ViewEmployeeCompensation extends ViewRecord
{
    protected static string $resource = EmployeeCompensationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}

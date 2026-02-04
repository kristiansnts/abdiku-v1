<?php

declare(strict_types=1);

namespace App\Filament\Resources\EmployeeWorkAssignments\Pages;

use App\Filament\Resources\EmployeeWorkAssignments\EmployeeWorkAssignmentResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditEmployeeWorkAssignment extends EditRecord
{
    protected static string $resource = EmployeeWorkAssignmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}

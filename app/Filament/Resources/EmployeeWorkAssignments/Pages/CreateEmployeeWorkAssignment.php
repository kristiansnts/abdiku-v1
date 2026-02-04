<?php

declare(strict_types=1);

namespace App\Filament\Resources\EmployeeWorkAssignments\Pages;

use App\Filament\Resources\EmployeeWorkAssignments\EmployeeWorkAssignmentResource;
use Filament\Resources\Pages\CreateRecord;

class CreateEmployeeWorkAssignment extends CreateRecord
{
    protected static string $resource = EmployeeWorkAssignmentResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}

<?php

declare(strict_types=1);

namespace App\Filament\Resources\EmployeeCompensations\Pages;

use App\Filament\Resources\EmployeeCompensations\EmployeeCompensationResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

final class EditEmployeeCompensation extends EditRecord
{
    protected static string $resource = EmployeeCompensationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}

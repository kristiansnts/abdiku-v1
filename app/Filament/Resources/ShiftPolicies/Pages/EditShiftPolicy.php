<?php

declare(strict_types=1);

namespace App\Filament\Resources\ShiftPolicies\Pages;

use App\Filament\Resources\ShiftPolicies\ShiftPolicyResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditShiftPolicy extends EditRecord
{
    protected static string $resource = ShiftPolicyResource::class;

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

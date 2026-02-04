<?php

declare(strict_types=1);

namespace App\Filament\Resources\ShiftPolicies\Pages;

use App\Filament\Resources\ShiftPolicies\ShiftPolicyResource;
use Filament\Resources\Pages\CreateRecord;

class CreateShiftPolicy extends CreateRecord
{
    protected static string $resource = ShiftPolicyResource::class;

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

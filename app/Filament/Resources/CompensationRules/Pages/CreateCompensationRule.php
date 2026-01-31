<?php

declare(strict_types=1);

namespace App\Filament\Resources\CompensationRules\Pages;

use App\Filament\Resources\CompensationRules\CompensationRuleResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateCompensationRule extends CreateRecord
{
    protected static string $resource = CompensationRuleResource::class;

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

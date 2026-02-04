<?php

declare(strict_types=1);

namespace App\Filament\Resources\WorkPatterns\Pages;

use App\Filament\Resources\WorkPatterns\WorkPatternResource;
use Filament\Resources\Pages\CreateRecord;

class CreateWorkPattern extends CreateRecord
{
    protected static string $resource = WorkPatternResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['company_id'] = auth()->user()?->company_id;

        // Ensure working_days is an array of integers
        if (isset($data['working_days'])) {
            $data['working_days'] = array_map('intval', $data['working_days']);
            sort($data['working_days']);
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}

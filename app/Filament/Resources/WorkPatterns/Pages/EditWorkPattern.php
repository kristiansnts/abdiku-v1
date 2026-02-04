<?php

declare(strict_types=1);

namespace App\Filament\Resources\WorkPatterns\Pages;

use App\Filament\Resources\WorkPatterns\WorkPatternResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditWorkPattern extends EditRecord
{
    protected static string $resource = WorkPatternResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
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

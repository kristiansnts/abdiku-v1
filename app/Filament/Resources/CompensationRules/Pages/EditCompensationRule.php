<?php

declare(strict_types=1);

namespace App\Filament\Resources\CompensationRules\Pages;

use App\Filament\Resources\CompensationRules\CompensationRuleResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

final class EditCompensationRule extends EditRecord
{
    protected static string $resource = CompensationRuleResource::class;

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

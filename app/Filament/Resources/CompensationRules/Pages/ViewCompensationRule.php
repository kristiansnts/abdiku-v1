<?php

declare(strict_types=1);

namespace App\Filament\Resources\CompensationRules\Pages;

use App\Filament\Resources\CompensationRules\CompensationRuleResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

final class ViewCompensationRule extends ViewRecord
{
    protected static string $resource = CompensationRuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}

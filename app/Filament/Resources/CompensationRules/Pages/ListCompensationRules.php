<?php

declare(strict_types=1);

namespace App\Filament\Resources\CompensationRules\Pages;

use App\Filament\Resources\CompensationRules\CompensationRuleResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

final class ListCompensationRules extends ListRecords
{
    protected static string $resource = CompensationRuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Filament\Resources\CompanyLocations\Pages;

use App\Filament\Resources\CompanyLocations\CompanyLocationResource;
use App\Models\CompanyLocation;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCompanyLocation extends EditRecord
{
    protected static string $resource = CompanyLocationResource::class;

    protected static ?string $title = 'Edit Lokasi Kantor';

    protected function afterSave(): void
    {
        // If set as default, unset all other locations
        if ($this->record->is_default) {
            CompanyLocation::where('company_id', $this->record->company_id)
                ->where('id', '!=', $this->record->id)
                ->update(['is_default' => false]);
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->before(function (DeleteAction $action) {
                    $count = CompanyLocation::where('company_id', $this->record->company_id)->count();
                    if ($count <= 1) {
                        $action->cancel();
                        \Filament\Notifications\Notification::make()
                            ->title('Tidak dapat dihapus')
                            ->body('Minimal harus ada satu lokasi kantor.')
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}

<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\CompanyLocation;
use Filament\Widgets\Widget;
use Illuminate\Contracts\View\View;

class CompanyLocationMapWidget extends Widget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected static string $view = 'filament.widgets.company-location-map-widget';

    public static function canView(): bool
    {
        // Only show widget if user has a company and is not a super admin
        $user = auth()->user();

        if (!$user || $user->hasRole(['super_admin', 'super-admin'])) {
            return false;
        }

        return $user->company !== null;
    }

    public function getLocations(): array
    {
        $companyId = auth()->user()?->company_id;

        if (!$companyId) {
            return [];
        }

        return CompanyLocation::query()
            ->where('company_id', $companyId)
            ->select(['id', 'name', 'address', 'latitude', 'longitude', 'geofence_radius_meters'])
            ->get()
            ->map(fn($location) => [
                'id' => $location->id,
                'name' => $location->name,
                'address' => $location->address,
                'lat' => $location->latitude,
                'lng' => $location->longitude,
                'radius' => $location->geofence_radius_meters,
            ])
            ->toArray();
    }
}

<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

class WelcomeOwnerWidget extends Widget
{
    protected static ?int $sort = 0;

    protected int|string|array $columnSpan = 'full';

    protected static string $view = 'filament.widgets.welcome-owner-widget';

    public static function canView(): bool
    {
        $user = auth()->user();

        // Only show for users without a company (new owners)
        if (!$user || $user->hasRole(['super_admin', 'super-admin'])) {
            return false;
        }

        return $user->company === null;
    }
}

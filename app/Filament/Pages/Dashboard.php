<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\CompanyLocationMapWidget;
use App\Filament\Widgets\DailyAttendanceWidget;
use App\Filament\Widgets\StatsOverview;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-home';

    protected static ?string $title = 'Dashboard';

    public function getWidgets(): array
    {
        return [
            StatsOverview::class,
            CompanyLocationMapWidget::class,
            DailyAttendanceWidget::class,
        ];
    }

    public function getColumns(): int|string|array
    {
        return 2;
    }
}

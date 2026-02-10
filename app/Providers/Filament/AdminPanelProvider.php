<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Facades\FilamentView;
use Filament\Widgets;
use App\Filament\Widgets\StatsOverview;
use App\Filament\Widgets\CompanyLocationMapWidget;
use App\Filament\Widgets\DailyAttendanceWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Blade;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->brandLogo(asset('images/payrollkami-logo.png'))
            ->brandLogoHeight('4rem')
            ->login()
            ->colors([
                'primary' => Color::Blue,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                StatsOverview::class,
                CompanyLocationMapWidget::class,
                DailyAttendanceWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->plugins([
                \BezhanSalleh\FilamentShield\FilamentShieldPlugin::make(),
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->databaseNotifications()
            ->databaseNotificationsPolling('5s')
            ->renderHook(
                'panels::head.end',
                fn(): string => Blade::render('
                    @if(config(\'services.google.maps_api_key\'))
                        <script src="https://maps.googleapis.com/maps/api/js?key={{ config(\'services.google.maps_api_key\') }}&libraries=places" defer></script>
                    @endif
                    <script>
                        // Debug: Log when notification polling happens
                        document.addEventListener("DOMContentLoaded", function() {
                            console.log("Filament Database Notifications - Polling enabled");

                            // Listen for Livewire updates
                            if (window.Livewire) {
                                Livewire.hook("commit", ({ component }) => {
                                    if (component.name === "database-notifications") {
                                        console.log("Notification polling update:", new Date().toLocaleTimeString());
                                    }
                                });
                            }
                        });
                    </script>
                    <style>
                        /* Make notification bell more visible */
                        [x-data*="NotificationsComponent"] button {
                            position: relative;
                            padding: 0.5rem !important;
                            background-color: rgba(59, 130, 246, 0.1) !important;
                            border-radius: 0.5rem !important;
                            transition: all 0.2s ease !important;
                        }

                        [x-data*="NotificationsComponent"] button:hover {
                            background-color: rgba(59, 130, 246, 0.2) !important;
                            transform: scale(1.05);
                        }

                        [x-data*="NotificationsComponent"] button svg {
                            width: 1.5rem !important;
                            height: 1.5rem !important;
                            color: rgb(59, 130, 246) !important;
                        }

                        /* Pulse animation for unread notifications */
                        [x-data*="NotificationsComponent"] button[aria-label*="notification"]:has([class*="badge"]) {
                            animation: pulse-notification 2s infinite;
                        }

                        @keyframes pulse-notification {
                            0%, 100% {
                                box-shadow: 0 0 0 0 rgba(59, 130, 246, 0.4);
                            }
                            50% {
                                box-shadow: 0 0 0 8px rgba(59, 130, 246, 0);
                            }
                        }

                        /* Make the badge (unread count) more prominent */
                        [x-data*="NotificationsComponent"] button [class*="badge"] {
                            background-color: rgb(239, 68, 68) !important;
                            color: white !important;
                            font-weight: 700 !important;
                            font-size: 0.75rem !important;
                            min-width: 1.25rem !important;
                            height: 1.25rem !important;
                        }
                    </style>
                ')
            );
    }
}

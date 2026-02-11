<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register our custom password broker manager that handles company-scoped tokens
        $this->app->singleton('auth.password', function ($app) {
            return new \App\Auth\Passwords\CompanyScopedPasswordBrokerManager($app);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::define('viewPulse', function (User $user) {
            return $user->isAdmin();
        });

        // Force HTTPS in production
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }
    }
}

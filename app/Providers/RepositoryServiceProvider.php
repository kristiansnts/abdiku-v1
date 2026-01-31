<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domain\Payroll\Contracts\EmployeeRepositoryInterface;
use App\Domain\Payroll\Contracts\PayrollAdditionRepositoryInterface;
use App\Domain\Payroll\Contracts\PayrollPeriodRepositoryInterface;
use App\Infrastructure\Repositories\EloquentEmployeeRepository;
use App\Infrastructure\Repositories\EloquentPayrollAdditionRepository;
use App\Infrastructure\Repositories\EloquentPayrollPeriodRepository;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Bind repository interfaces to their Eloquent implementations
        $this->app->bind(EmployeeRepositoryInterface::class, EloquentEmployeeRepository::class);
        $this->app->bind(PayrollPeriodRepositoryInterface::class, EloquentPayrollPeriodRepository::class);
        $this->app->bind(PayrollAdditionRepositoryInterface::class, EloquentPayrollAdditionRepository::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
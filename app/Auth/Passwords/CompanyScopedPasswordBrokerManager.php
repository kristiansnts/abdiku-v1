<?php

declare(strict_types=1);

namespace App\Auth\Passwords;

use Illuminate\Auth\Passwords\PasswordBrokerManager;

class CompanyScopedPasswordBrokerManager extends PasswordBrokerManager
{
    /**
     * Resolve the given broker.
     *
     * @param  string|null  $name
     * @return \Illuminate\Contracts\Auth\PasswordBroker
     *
     * @throws \InvalidArgumentException
     */
    protected function resolve($name)
    {
        $config = $this->getConfig($name);

        if (is_null($config)) {
            throw new \InvalidArgumentException("Password resetter [{$name}] is not defined.");
        }

        // Use our custom repository for company-scoped tokens
        return new \Illuminate\Auth\Passwords\PasswordBroker(
            $this->createCompanyScopedTokenRepository($config),
            $this->app['auth']->createUserProvider($config['provider'] ?? null)
        );
    }

    /**
     * Create a company-scoped token repository instance based on the given configuration.
     *
     * @param  array  $config
     * @return \App\Auth\Passwords\CompanyScopedDatabaseTokenRepository
     */
    protected function createCompanyScopedTokenRepository(array $config)
    {
        $key = $this->app['config']['app.key'];

        if (str_starts_with($key, 'base64:')) {
            $key = base64_decode(substr($key, 7));
        }

        return new CompanyScopedDatabaseTokenRepository(
            $this->app['db']->connection($config['connection'] ?? null),
            $this->app['hash'],
            $config['table'],
            $key,
            $config['expire'],
            $config['throttle'] ?? 0
        );
    }
}

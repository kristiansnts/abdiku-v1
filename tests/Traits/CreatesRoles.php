<?php

namespace Tests\Traits;

use Spatie\Permission\Models\Role;

trait CreatesRoles
{
    protected function setUpRoles(): void
    {
        // Clear any existing roles in test database
        Role::truncate();

        // Create all required roles
        $roles = ['hr', 'owner', 'employee', 'admin', 'super_admin'];

        foreach ($roles as $roleName) {
            Role::create([
                'name' => $roleName,
                'guard_name' => 'web',
            ]);
        }
    }
}

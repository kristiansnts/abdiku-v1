<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // Create roles if they don't exist
        $roles = [
            'owner' => ['guard_name' => 'web'],
            'hr' => ['guard_name' => 'web'],
            'employee' => ['guard_name' => 'web'],
        ];

        foreach ($roles as $roleName => $attributes) {
            Role::firstOrCreate(['name' => $roleName], $attributes);
        }

        $this->command->info('✅ Roles created: ' . implode(', ', array_keys($roles)));
    }
}

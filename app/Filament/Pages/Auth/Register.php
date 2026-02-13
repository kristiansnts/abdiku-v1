<?php

namespace App\Filament\Pages\Auth;

use App\Models\Company;
use App\Models\User;
use Filament\Pages\Auth\Register as BaseRegister;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class Register extends BaseRegister
{
    protected function handleRegistration(array $data): User
    {
        return DB::transaction(function () use ($data) {
            // 1. Buat Perusahaan (Tenant) baru dengan nama placeholder
            $company = Company::create([
                'name' => 'Perusahaan Baru',
            ]);

            // 2. Buat User sebagai Owner
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
                'company_id' => $company->id,
                'role' => 'OWNER', // Gunakan sistem role internal kamu
            ]);

            // 3. Assign Spatie Role jika menggunakan Filament Shield
            $ownerRole = Role::where('name', 'OWNER')->first();
            if ($ownerRole) {
                $user->assignRole($ownerRole);
            }

            return $user;
        });
    }
}

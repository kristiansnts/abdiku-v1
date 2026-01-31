<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use BezhanSalleh\FilamentShield\Support\Utils;
use Spatie\Permission\PermissionRegistrar;

class ShieldSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $rolesWithPermissions = '[{"name":"super-admin","guard_name":"web","permissions":["view_attendance::attendance::decision","view_any_attendance::attendance::decision","create_attendance::attendance::decision","update_attendance::attendance::decision","delete_attendance::attendance::decision","view_attendance::attendance::record","view_any_attendance::attendance::record","create_attendance::attendance::record","update_attendance::attendance::record","delete_attendance::attendance::record","view_companies::company","view_any_companies::company","create_companies::company","update_companies::company","delete_companies::company","view_employee::compensations::employee::compensation","view_any_employee::compensations::employee::compensation","create_employee::compensations::employee::compensation","update_employee::compensations::employee::compensation","delete_employee::compensations::employee::compensation","view_employees::employee","view_any_employees::employee","create_employees::employee","update_employees::employee","delete_employees::employee","view_payroll::override::request","view_any_payroll::override::request","create_payroll::override::request","update_payroll::override::request","delete_payroll::override::request","view_payroll::payroll::batch","view_any_payroll::payroll::batch","create_payroll::payroll::batch","update_payroll::payroll::batch","delete_payroll::payroll::batch","view_payroll::payroll::override","view_any_payroll::payroll::override","create_payroll::payroll::override","update_payroll::payroll::override","delete_payroll::payroll::override","view_payroll::payroll::period","view_any_payroll::payroll::period","create_payroll::payroll::period","update_payroll::payroll::period","delete_payroll::payroll::period","view_payroll::payroll::row","view_any_payroll::payroll::row","create_payroll::payroll::row","update_payroll::payroll::row","delete_payroll::payroll::row","view_role","view_any_role","create_role","update_role","delete_role","delete_any_role","view_users::user","view_any_users::user","create_users::user","update_users::user","delete_users::user"]},{"name":"owner","guard_name":"web","permissions":["view_attendance::attendance::decision","view_any_attendance::attendance::decision","create_attendance::attendance::decision","update_attendance::attendance::decision","delete_attendance::attendance::decision","view_attendance::attendance::record","view_any_attendance::attendance::record","create_attendance::attendance::record","update_attendance::attendance::record","delete_attendance::attendance::record","view_companies::company","view_any_companies::company","create_companies::company","update_companies::company","delete_companies::company","view_employee::compensations::employee::compensation","view_any_employee::compensations::employee::compensation","create_employee::compensations::employee::compensation","update_employee::compensations::employee::compensation","delete_employee::compensations::employee::compensation","view_employees::employee","view_any_employees::employee","create_employees::employee","update_employees::employee","delete_employees::employee","view_payroll::override::request","view_any_payroll::override::request","create_payroll::override::request","update_payroll::override::request","delete_payroll::override::request","view_payroll::payroll::batch","view_any_payroll::payroll::batch","create_payroll::payroll::batch","update_payroll::payroll::batch","delete_payroll::payroll::batch","view_payroll::payroll::override","view_any_payroll::payroll::override","create_payroll::payroll::override","update_payroll::payroll::override","delete_payroll::payroll::override","view_payroll::payroll::period","view_any_payroll::payroll::period","create_payroll::payroll::period","update_payroll::payroll::period","delete_payroll::payroll::period","view_payroll::payroll::row","view_any_payroll::payroll::row","create_payroll::payroll::row","update_payroll::payroll::row","delete_payroll::payroll::row","view_users::user","view_any_users::user","create_users::user","update_users::user","delete_users::user"]},{"name":"hr","guard_name":"web","permissions":["view_attendance::attendance::decision","view_any_attendance::attendance::decision","create_attendance::attendance::decision","update_attendance::attendance::decision","delete_attendance::attendance::decision","view_attendance::attendance::record","view_any_attendance::attendance::record","create_attendance::attendance::record","update_attendance::attendance::record","delete_attendance::attendance::record","view_employee::compensations::employee::compensation","view_any_employee::compensations::employee::compensation","create_employee::compensations::employee::compensation","update_employee::compensations::employee::compensation","delete_employee::compensations::employee::compensation","view_employees::employee","view_any_employees::employee","create_employees::employee","update_employees::employee","delete_employees::employee","view_payroll::override::request","view_any_payroll::override::request","create_payroll::override::request","update_payroll::override::request","delete_payroll::override::request","view_payroll::payroll::batch","view_any_payroll::payroll::batch","create_payroll::payroll::batch","update_payroll::payroll::batch","delete_payroll::payroll::batch","view_payroll::payroll::override","view_any_payroll::payroll::override","create_payroll::payroll::override","update_payroll::payroll::override","delete_payroll::payroll::override","view_payroll::payroll::period","view_any_payroll::payroll::period","create_payroll::payroll::period","update_payroll::payroll::period","delete_payroll::payroll::period","view_payroll::payroll::row","view_any_payroll::payroll::row","create_payroll::payroll::row","update_payroll::payroll::row","delete_payroll::payroll::row","view_users::user","view_any_users::user","create_users::user","update_users::user","delete_users::user"]},{"name":"employee","guard_name":"web","permissions":[]},{"name":"super_admin","guard_name":"web","permissions":["view_attendance::attendance::decision","view_any_attendance::attendance::decision","create_attendance::attendance::decision","update_attendance::attendance::decision","delete_attendance::attendance::decision","view_attendance::attendance::record","view_any_attendance::attendance::record","create_attendance::attendance::record","update_attendance::attendance::record","delete_attendance::attendance::record","view_companies::company","view_any_companies::company","create_companies::company","update_companies::company","delete_companies::company","view_employee::compensations::employee::compensation","view_any_employee::compensations::employee::compensation","create_employee::compensations::employee::compensation","update_employee::compensations::employee::compensation","delete_employee::compensations::employee::compensation","view_employees::employee","view_any_employees::employee","create_employees::employee","update_employees::employee","delete_employees::employee","view_payroll::override::request","view_any_payroll::override::request","create_payroll::override::request","update_payroll::override::request","delete_payroll::override::request","view_payroll::payroll::batch","view_any_payroll::payroll::batch","create_payroll::payroll::batch","update_payroll::payroll::batch","delete_payroll::payroll::batch","view_payroll::payroll::override","view_any_payroll::payroll::override","create_payroll::payroll::override","update_payroll::payroll::override","delete_payroll::payroll::override","view_payroll::payroll::period","view_any_payroll::payroll::period","create_payroll::payroll::period","update_payroll::payroll::period","delete_payroll::payroll::period","view_payroll::payroll::row","view_any_payroll::payroll::row","create_payroll::payroll::row","update_payroll::payroll::row","delete_payroll::payroll::row","view_role","view_any_role","create_role","update_role","delete_role","delete_any_role","view_users::user","view_any_users::user","create_users::user","update_users::user","delete_users::user"]}]';
        $directPermissions = '[]';

        static::makeRolesWithPermissions($rolesWithPermissions);
        static::makeDirectPermissions($directPermissions);

        $this->command->info('Shield Seeding Completed.');
    }

    protected static function makeRolesWithPermissions(string $rolesWithPermissions): void
    {
        if (! blank($rolePlusPermissions = json_decode($rolesWithPermissions, true))) {
            /** @var Model $roleModel */
            $roleModel = Utils::getRoleModel();
            /** @var Model $permissionModel */
            $permissionModel = Utils::getPermissionModel();

            foreach ($rolePlusPermissions as $rolePlusPermission) {
                $role = $roleModel::firstOrCreate([
                    'name' => $rolePlusPermission['name'],
                    'guard_name' => $rolePlusPermission['guard_name'],
                ]);

                if (! blank($rolePlusPermission['permissions'])) {
                    $permissionModels = collect($rolePlusPermission['permissions'])
                        ->map(fn ($permission) => $permissionModel::firstOrCreate([
                            'name' => $permission,
                            'guard_name' => $rolePlusPermission['guard_name'],
                        ]))
                        ->all();

                    $role->syncPermissions($permissionModels);
                }
            }
        }
    }

    public static function makeDirectPermissions(string $directPermissions): void
    {
        if (! blank($permissions = json_decode($directPermissions, true))) {
            /** @var Model $permissionModel */
            $permissionModel = Utils::getPermissionModel();

            foreach ($permissions as $permission) {
                if ($permissionModel::whereName($permission)->doesntExist()) {
                    $permissionModel::create([
                        'name' => $permission['name'],
                        'guard_name' => $permission['guard_name'],
                    ]);
                }
            }
        }
    }
}

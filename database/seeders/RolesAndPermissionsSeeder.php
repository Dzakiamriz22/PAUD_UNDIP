<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        /**
         * ======================
         * PERMISSIONS
         * ======================
         */
        Permission::updateOrCreate(
            ['name' => 'access_log_viewer', 'guard_name' => 'web'],
            ['name' => 'access_log_viewer', 'guard_name' => 'web']
        );

        /**
         * ======================
         * ROLES
         * ======================
         */
        $roles = [
            'super_admin',
            'admin',
            'operator',

            // PAUD
            'guru',
            'bendahara',
            'kepala_sekolah',
        ];

        foreach ($roles as $roleName) {
            $role = Role::updateOrCreate(
                ['name' => $roleName, 'guard_name' => 'web'],
                ['updated_at' => now()]
            );

            // Hak khusus super admin
            if ($roleName === 'super_admin') {
                $role->givePermissionTo('access_log_viewer');
            }
        }
    }
}
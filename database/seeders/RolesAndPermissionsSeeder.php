<?php

namespace Database\Seeders;

use App\Filament\Resources\Shield\RoleResource;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // create permissions
        // Buat permission jika belum ada
        Permission::updateOrCreate(
            ['name' => 'access_log_viewer', 'guard_name' => 'web'],
            ['name' => 'access_log_viewer', 'guard_name' => 'web']
        );

        // Settlement permissions
        $settlementPermissions = [
            'view_any_settlement',
            'view_settlement',
            'create_settlement',
            'update_settlement',
            'delete_settlement',
            'delete_any_settlement',
        ];

        foreach ($settlementPermissions as $permission) {
            Permission::updateOrCreate(
                ['name' => $permission, 'guard_name' => 'web'],
                ['name' => $permission, 'guard_name' => 'web']
            );
        }

        $roles = ["super_admin", "admin", "operator"]; // author

        foreach ($roles as $role) {
            $roleCreated = Role::updateOrCreate(
                ['name' => $role, 'guard_name' => 'web'],
                ['updated_at' => now()] // agar tetap menyentuh record saat re-run
            );

            if ($role === 'super_admin') {
                $roleCreated->givePermissionTo('access_log_viewer');
            }
        }
    }
}

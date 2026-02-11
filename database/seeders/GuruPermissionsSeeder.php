<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class GuruPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        /**
         * ======================
         * PERMISSIONS FOR GURU
         * ======================
         * 
         * Guru memiliki akses READ-ONLY
         * Akses terbatas ke kelas mereka sendiri (via Scope)
         * Tidak bisa CREATE, UPDATE, DELETE apapun
         */
        
        // Class Permissions (Read-only)
        $classPermissions = [
            'view_school_class' => 'Lihat Kelas (Kelas Sendiri)',
            'view_any_school_class' => 'Lihat Daftar Kelas',
        ];

        // Student Permissions (Read-only, own class only)
        $studentPermissions = [
            'view_student' => 'Lihat Data Siswa',
            'view_any_student' => 'Lihat Daftar Siswa',
        ];

        // Invoice/Payment Permissions (Read-only - Riwayat Pembayaran)
        $invoicePermissions = [
            'view_invoice' => 'Lihat Invoice Siswa',
            'view_any_invoice' => 'Lihat Daftar Invoice',
        ];

        // Receipt/Kwitansi Permissions (Read-only)
        $receiptPermissions = [
            'view_receipt' => 'Lihat Kwitansi Pembayaran',
            'view_any_receipt' => 'Lihat Daftar Kwitansi',
        ];

        // Student Class History - Read only (own class)
        $studentClassHistoryPermissions = [
            'view_student_class_history' => 'Lihat Riwayat Kelas Siswa',
            'view_any_student_class_history' => 'Lihat Daftar Riwayat Kelas',
        ];

        // Combine all permissions
        $allPermissions = array_merge(
            $classPermissions,
            $studentPermissions,
            $invoicePermissions,
            $receiptPermissions,
            $studentClassHistoryPermissions
        );

        // Create permissions
        foreach ($allPermissions as $permissionName => $label) {
            Permission::updateOrCreate(
                ['name' => $permissionName, 'guard_name' => 'web'],
                ['name' => $permissionName, 'guard_name' => 'web']
            );
        }

        // Get guru role
        $guruRole = Role::where('name', 'guru')->firstOrFail();

        // Assign all permissions to guru role
        foreach (array_keys($allPermissions) as $permissionName) {
            $guruRole->givePermissionTo($permissionName);
        }

        $this->command->info('✓ Guru permissions created and assigned:');
        $this->command->info('  ├─ View Class (Own class only via scope)');
        $this->command->info('  ├─ View Students (Own class students only)');
        $this->command->info('  ├─ View Student History (Own class students)');
        $this->command->info('  ├─ View Invoice (Own class students payments)');
        $this->command->info('  ├─ View Receipts (Own class students receipts)');
        $this->command->info('  └─ Restrictions: Read-only, scoped by assigned class');
    }
}

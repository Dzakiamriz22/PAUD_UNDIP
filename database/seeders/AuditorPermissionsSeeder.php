<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AuditorPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        /**
         * ======================
         * PERMISSIONS FOR AUDITOR
         * ======================
         * 
         * Auditor memiliki akses READ-ONLY ke semua fitur PAUD
         * Tidak bisa melakukan CREATE, UPDATE, DELETE
         * Akses untuk verifikasi dan monitoring
         */
        
        // Academic Year Permissions (Read-only)
        $academicYearPermissions = [
            'view_academic_year' => 'Lihat Tahun Ajaran',
            'view_any_academic_year' => 'Lihat Daftar Tahun Ajaran',
        ];

        // Class Permissions (Read-only)
        $classPermissions = [
            'view_school_class' => 'Lihat Kelas',
            'view_any_school_class' => 'Lihat Daftar Kelas',
        ];

        // Student Permissions (Read-only)
        $studentPermissions = [
            'view_student' => 'Lihat Data Siswa',
            'view_any_student' => 'Lihat Daftar Siswa',
        ];

        // Student Class History Permissions (Read-only)
        $studentClassHistoryPermissions = [
            'view_student_class_history' => 'Lihat Riwayat Kelas Siswa',
            'view_any_student_class_history' => 'Lihat Daftar Riwayat Kelas',
        ];

        // Invoice/Payment Permissions (Read-only)
        $invoicePermissions = [
            'view_invoice' => 'Lihat Invoice/Tagihan',
            'view_any_invoice' => 'Lihat Daftar Invoice',
        ];

        // Receipt/Kwitansi Permissions (Read-only)
        $receiptPermissions = [
            'view_receipt' => 'Lihat Kwitansi Pembayaran',
            'view_any_receipt' => 'Lihat Daftar Kwitansi',
        ];

        // Tariff Permissions (Read-only)
        $tariffPermissions = [
            'view_tariff' => 'Lihat Tarif',
            'view_any_tariff' => 'Lihat Daftar Tarif',
        ];

        // Virtual Account Permissions (Read-only)
        $virtualAccountPermissions = [
            'view_virtual_account' => 'Lihat Rekening Virtual',
            'view_any_virtual_account' => 'Lihat Daftar Rekening Virtual',
        ];

        // Financial Report Permissions (Read-only)
        $financialReportPermissions = [
            'view_financial::report' => 'Lihat Laporan Keuangan',
            'view_any_financial::report' => 'Lihat Daftar Laporan Keuangan',
        ];

        // School Permissions (Read-only)
        $schoolPermissions = [
            'view_school' => 'Lihat Data Sekolah',
            'view_any_school' => 'Lihat Daftar Sekolah',
        ];

        // Activity Log & Audit Trail (Already has from RolesAndPermissionsSeeder)
        $auditPermissions = [
            'access_log_viewer' => 'Akses Activity Log Viewer',
        ];

        // Combine all permissions
        $allPermissions = array_merge(
            $academicYearPermissions,
            $classPermissions,
            $studentPermissions,
            $studentClassHistoryPermissions,
            $invoicePermissions,
            $receiptPermissions,
            $tariffPermissions,
            $virtualAccountPermissions,
            $financialReportPermissions,
            $schoolPermissions,
            $auditPermissions
        );

        // Create permissions
        foreach ($allPermissions as $permissionName => $label) {
            Permission::updateOrCreate(
                ['name' => $permissionName, 'guard_name' => 'web'],
                ['name' => $permissionName, 'guard_name' => 'web']
            );
        }

        // Get auditor role
        $auditorRole = Role::where('name', 'auditor')->firstOrFail();

        // Assign all permissions to auditor role
        foreach (array_keys($allPermissions) as $permissionName) {
            $auditorRole->givePermissionTo($permissionName);
        }

        $this->command->info('✓ Auditor permissions created and assigned:');
        $this->command->info('  ├─ Academic Year (Read-only)');
        $this->command->info('  ├─ Classes (Read-only)');
        $this->command->info('  ├─ Students (Read-only)');
        $this->command->info('  ├─ Student Class History (Read-only)');
        $this->command->info('  ├─ Invoices (Read-only)');
        $this->command->info('  ├─ Receipts (Read-only)');
        $this->command->info('  ├─ Tariffs (Read-only)');
        $this->command->info('  ├─ Virtual Accounts (Read-only)');
        $this->command->info('  ├─ Financial Reports (Read-only)');
        $this->command->info('  ├─ Schools (Read-only)');
        $this->command->info('  └─ Activity Log Access');
    }
}

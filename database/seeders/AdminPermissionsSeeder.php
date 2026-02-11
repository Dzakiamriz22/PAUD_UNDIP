<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AdminPermissionsSeeder extends Seeder
{
    /**
     * Admin Role SOP:
     * - Full system administration
     * - Full CRUD untuk semua resources
     * - Manage users, roles, dan permissions
     * - Configure system settings
     * - Monitor semua aktivitas
     * 
     * NO DELETE restrictions:
     * - Critical data seperti academic year, school tidak boleh delete
     * - Hanya super_admin yang bisa delete
     */
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        /**
         * ======================
         * PERMISSIONS FOR ADMIN
         * ======================
         */
        
        // Academic Year - Create, Read, Update (No delete)
        $academicYearPermissions = [
            'view_academic_year' => 'Lihat Tahun Ajaran',
            'view_any_academic_year' => 'Lihat Daftar Tahun Ajaran',
            'create_academic_year' => 'Buat Tahun Ajaran Baru',
            'update_academic_year' => 'Edit Tahun Ajaran',
        ];

        // Class - Full CRUD
        $classPermissions = [
            'view_school_class' => 'Lihat Kelas',
            'view_any_school_class' => 'Lihat Daftar Kelas',
            'create_school_class' => 'Buat Kelas Baru',
            'update_school_class' => 'Edit Kelas',
            'delete_school_class' => 'Hapus Kelas',
        ];

        // Student - Full CRUD
        $studentPermissions = [
            'view_student' => 'Lihat Data Siswa',
            'view_any_student' => 'Lihat Daftar Siswa',
            'create_student' => 'Tambah Siswa Baru',
            'update_student' => 'Edit Data Siswa',
            'delete_student' => 'Hapus Siswa',
        ];

        // Student Class History - Create, Read, Update
        $studentClassHistoryPermissions = [
            'view_student_class_history' => 'Lihat Riwayat Kelas Siswa',
            'view_any_student_class_history' => 'Lihat Daftar Riwayat Kelas',
            'create_student_class_history' => 'Buat Riwayat Kelas Baru',
            'update_student_class_history' => 'Edit Riwayat Kelas',
        ];

        // Invoice - Full CRUD
        $invoicePermissions = [
            'view_invoice' => 'Lihat Invoice',
            'view_any_invoice' => 'Lihat Daftar Invoice',
            'create_invoice' => 'Buat Invoice Baru',
            'update_invoice' => 'Edit Invoice',
            'delete_invoice' => 'Hapus Invoice',
        ];

        // Receipt - Full CRUD
        $receiptPermissions = [
            'view_receipt' => 'Lihat Kwitansi',
            'view_any_receipt' => 'Lihat Daftar Kwitansi',
            'create_receipt' => 'Buat Kwitansi Baru',
            'update_receipt' => 'Edit Kwitansi',
            'delete_receipt' => 'Hapus Kwitansi',
        ];

        // Tariff - Full CRUD
        $tariffPermissions = [
            'view_tariff' => 'Lihat Tarif',
            'view_any_tariff' => 'Lihat Daftar Tarif',
            'create_tariff' => 'Buat Tarif Baru',
            'update_tariff' => 'Edit Tarif',
            'delete_tariff' => 'Hapus Tarif',
        ];

        // Virtual Account - Full CRUD
        $virtualAccountPermissions = [
            'view_virtual_account' => 'Lihat Rekening Virtual',
            'view_any_virtual_account' => 'Lihat Daftar Rekening Virtual',
            'create_virtual_account' => 'Buat Rekening Virtual',
            'update_virtual_account' => 'Edit Rekening Virtual',
            'delete_virtual_account' => 'Hapus Rekening Virtual',
        ];

        // Financial Report - Create, Read
        $financialReportPermissions = [
            'view_financial::report' => 'Lihat Laporan Keuangan',
            'view_any_financial::report' => 'Lihat Daftar Laporan Keuangan',
            'create_financial::report' => 'Buat Laporan Keuangan',
        ];

        // School - Read & Update (No delete - critical data)
        $schoolPermissions = [
            'view_school' => 'Lihat Data Sekolah',
            'view_any_school' => 'Lihat Daftar Sekolah',
            'update_school' => 'Edit Data Sekolah',
        ];

        // User Management - Can manage non-admin users
        $userPermissions = [
            'view_user' => 'Lihat Pengguna',
            'view_any_user' => 'Lihat Daftar Pengguna',
            'create_user' => 'Buat Pengguna Baru',
            'update_user' => 'Edit Data Pengguna',
        ];

        // Activity Log Access
        $systemPermissions = [
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
            $userPermissions,
            $systemPermissions
        );

        // Create permissions
        foreach ($allPermissions as $permissionName => $label) {
            Permission::updateOrCreate(
                ['name' => $permissionName, 'guard_name' => 'web'],
                ['name' => $permissionName, 'guard_name' => 'web']
            );
        }

        // Get admin role
        $adminRole = Role::where('name', 'admin')->firstOrFail();

        // Assign all permissions to admin role
        foreach (array_keys($allPermissions) as $permissionName) {
            $adminRole->givePermissionTo($permissionName);
        }

        $this->command->info('✓ Admin permissions created and assigned');
        $this->command->info('  ├─ Full CRUD: Classes, Students, Invoices, Receipts, Tariffs, Virtual Accounts');
        $this->command->info('  ├─ Create & Read: Academic Year, Student History, Financial Reports');
        $this->command->info('  ├─ Read & Update: School');
        $this->command->info('  ├─ User Management: Create & manage non-admin users');
        $this->command->info('  └─ System: Activity Log access');
    }
}

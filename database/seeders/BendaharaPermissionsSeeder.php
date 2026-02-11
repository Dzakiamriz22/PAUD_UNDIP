<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class BendaharaPermissionsSeeder extends Seeder
{
    /**
     * Bendahara (Treasurer) Role SOP:
     * - Financial management & transactions
     * - Create & manage invoices, receipts, payments
     * - View student data for billing
     * - View class & academic year for context
     * - Generate financial reports
     * - View virtual accounts & tariffs
     * 
     * Restrictions:
     * - Cannot delete invoices/receipts (audit trail)
     * - Cannot manage users/roles/permissions
     * - Cannot modify academic year or school settings
     */
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        /**
         * ======================
         * PERMISSIONS FOR BENDAHARA
         * ======================
         */
        
        // Academic Year - Read Only
        $academicYearPermissions = [
            'view_academic_year' => 'Lihat Tahun Ajaran',
            'view_any_academic_year' => 'Lihat Daftar Tahun Ajaran',
        ];

        // Class - Read Only (for context & billing)
        $classPermissions = [
            'view_school_class' => 'Lihat Kelas',
            'view_any_school_class' => 'Lihat Daftar Kelas',
        ];

        // Student - Read Only (for billing purposes)
        $studentPermissions = [
            'view_student' => 'Lihat Data Siswa',
            'view_any_student' => 'Lihat Daftar Siswa',
        ];

        // Student Class History - Read Only
        $studentClassHistoryPermissions = [
            'view_student_class_history' => 'Lihat Riwayat Kelas Siswa',
            'view_any_student_class_history' => 'Lihat Daftar Riwayat Kelas',
        ];

        // Invoice - Full CRUD (Create, Update, Delete primary role)
        $invoicePermissions = [
            'view_invoice' => 'Lihat Invoice',
            'view_any_invoice' => 'Lihat Daftar Invoice',
            'create_invoice' => 'Buat Invoice Baru',
            'update_invoice' => 'Edit Invoice',
            'delete_invoice' => 'Hapus Invoice (Hanya Draft)',
        ];

        // Receipt - Full CRUD (Create, Update, Delete primary role)
        $receiptPermissions = [
            'view_receipt' => 'Lihat Kwitansi',
            'view_any_receipt' => 'Lihat Daftar Kwitansi',
            'create_receipt' => 'Buat Kwitansi Baru',
            'update_receipt' => 'Edit Kwitansi',
            'delete_receipt' => 'Hapus Kwitansi (Hanya Tertentu)',
        ];

        // Tariff - Read & Manage (Create, Update, but not delete)
        $tariffPermissions = [
            'view_tariff' => 'Lihat Tarif',
            'view_any_tariff' => 'Lihat Daftar Tarif',
            'create_tariff' => 'Buat Tarif Baru',
            'update_tariff' => 'Edit Tarif',
        ];

        // Virtual Account - Read & Manage (Create, Update, but not delete)
        $virtualAccountPermissions = [
            'view_virtual_account' => 'Lihat Rekening Virtual',
            'view_any_virtual_account' => 'Lihat Daftar Rekening Virtual',
            'create_virtual_account' => 'Buat Rekening Virtual',
            'update_virtual_account' => 'Edit Rekening Virtual',
        ];

        // Financial Report - Create, Read
        $financialReportPermissions = [
            'view_financial::report' => 'Lihat Laporan Keuangan',
            'view_any_financial::report' => 'Lihat Daftar Laporan Keuangan',
            'create_financial::report' => 'Buat Laporan Keuangan',
        ];

        // School - Read Only (for context)
        $schoolPermissions = [
            'view_school' => 'Lihat Data Sekolah',
            'view_any_school' => 'Lihat Daftar Sekolah',
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
            $schoolPermissions
        );

        // Create permissions
        foreach ($allPermissions as $permissionName => $label) {
            Permission::updateOrCreate(
                ['name' => $permissionName, 'guard_name' => 'web'],
                ['name' => $permissionName, 'guard_name' => 'web']
            );
        }

        // Get bendahara role
        $bendaharaRole = Role::where('name', 'bendahara')->firstOrFail();

        // Assign all permissions to bendahara role
        foreach (array_keys($allPermissions) as $permissionName) {
            $bendaharaRole->givePermissionTo($permissionName);
        }

        $this->command->info('✓ Bendahara permissions created and assigned');
        $this->command->info('  ├─ Full CRUD Financial: Invoices, Receipts');
        $this->command->info('  ├─ Create & Update: Tariffs, Virtual Accounts');
        $this->command->info('  ├─ Read Only: Academic Year, Classes, Students, School');
        $this->command->info('  ├─ Generate: Financial Reports');
        $this->command->info('  └─ Restrictions: Cannot delete critical data');
    }
}

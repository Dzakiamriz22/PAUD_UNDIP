<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class KepsekPermissionsSeeder extends Seeder
{
    /**
     * Kepala Sekolah (Principal) Role SOP:
     * - Full visibility into all school operations
     * - View all academic & financial data
     * - Approve invoices & payment policies
     * - Manage academic year activation
     * - Approve student enrollments
     * - Review activity logs for compliance
     * - Strategic decision-making support
     * 
     * Restrictions:
     * - Cannot delete data (audit trail)
     * - Can modify selected data (academic year, tariffs, approvals)
     * - No direct user management (admin handles)
     * - No access to system permissions
     */
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        /**
         * ======================
         * PERMISSIONS FOR KEPALA SEKOLAH
         * ======================
         */
        
        // Academic Year - Read & Update (can activate/deactivate semester)
        $academicYearPermissions = [
            'view_academic_year' => 'Lihat Tahun Ajaran',
            'view_any_academic_year' => 'Lihat Daftar Tahun Ajaran',
            'update_academic_year' => 'Edit Tahun Ajaran (Set Semester Aktif)',
        ];

        // Class - Full Read + Update (can assign teachers)
        $classPermissions = [
            'view_school_class' => 'Lihat Kelas',
            'view_any_school_class' => 'Lihat Daftar Kelas',
            'update_school_class' => 'Edit Kelas (Assign Teacher, etc)',
        ];

        // Student - Full Read + Update (can approve enrollments)
        $studentPermissions = [
            'view_student' => 'Lihat Data Siswa',
            'view_any_student' => 'Lihat Daftar Siswa',
            'update_student' => 'Edit Data Siswa (Approve Enrollment, etc)',
        ];

        // Student Class History - Full Read + Update
        $studentClassHistoryPermissions = [
            'view_student_class_history' => 'Lihat Riwayat Kelas Siswa',
            'view_any_student_class_history' => 'Lihat Daftar Riwayat Kelas',
            'update_student_class_history' => 'Edit Riwayat Kelas (Approvals)',
        ];

        // Invoice - Full Read + Update (can approve/verify)
        $invoicePermissions = [
            'view_invoice' => 'Lihat Invoice',
            'view_any_invoice' => 'Lihat Daftar Invoice',
            'update_invoice' => 'Edit Invoice (Approve, Set Status)',
        ];

        // Receipt - Full Read + Update (can verify receipts)
        $receiptPermissions = [
            'view_receipt' => 'Lihat Kwitansi',
            'view_any_receipt' => 'Lihat Daftar Kwitansi',
            'update_receipt' => 'Edit Kwitansi (Verify, Set Status)',
        ];

        // Tariff - Read + Create & Update (can set payment policies)
        $tariffPermissions = [
            'view_tariff' => 'Lihat Tarif',
            'view_any_tariff' => 'Lihat Daftar Tarif',
            'create_tariff' => 'Buat Tarif Baru',
            'update_tariff' => 'Edit Tarif Pembayaran',
        ];

        // Virtual Account - Read + Create & Update
        $virtualAccountPermissions = [
            'view_virtual_account' => 'Lihat Rekening Virtual',
            'view_any_virtual_account' => 'Lihat Daftar Rekening Virtual',
            'create_virtual_account' => 'Buat Rekening Virtual',
            'update_virtual_account' => 'Edit Rekening Virtual',
        ];

        // Financial Report - Full Read + Create
        $financialReportPermissions = [
            'view_financial::report' => 'Lihat Laporan Keuangan',
            'view_any_financial::report' => 'Lihat Daftar Laporan Keuangan',
            'create_financial::report' => 'Buat & Generate Laporan Keuangan',
        ];

        // School - Read & Update (can modify school info)
        $schoolPermissions = [
            'view_school' => 'Lihat Data Sekolah',
            'view_any_school' => 'Lihat Daftar Sekolah',
            'update_school' => 'Edit Data Sekolah',
        ];

        // Activity Log - Full access for oversight
        $systemPermissions = [
            'access_log_viewer' => 'Akses Activity Log Viewer (Oversight)',
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
            $systemPermissions
        );

        // Create permissions
        foreach ($allPermissions as $permissionName => $label) {
            Permission::updateOrCreate(
                ['name' => $permissionName, 'guard_name' => 'web'],
                ['name' => $permissionName, 'guard_name' => 'web']
            );
        }

        // Get kepala sekolah role
        $kepsekRole = Role::where('name', 'kepala_sekolah')->firstOrFail();

        // Assign all permissions to kepala sekolah role
        foreach (array_keys($allPermissions) as $permissionName) {
            $kepsekRole->givePermissionTo($permissionName);
        }

        $this->command->info('✓ Kepala Sekolah permissions created and assigned');
        $this->command->info('  ├─ Full Access: View all academic & financial data');
        $this->command->info('  ├─ Approval Powers: Invoices, Receipts, Student Enrollments');
        $this->command->info('  ├─ Management: Tariffs, Tarif Policies, Academic Year');
        $this->command->info('  ├─ School Oversight: Update school info, review activity logs');
        $this->command->info('  └─ Restrictions: No delete, no system/user management');
    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
            GuruPermissionsSeeder::class,
            AdminPermissionsSeeder::class,
            BendaharaPermissionsSeeder::class,
            KepsekPermissionsSeeder::class,
            AuditorPermissionsSeeder::class,
            SuperadminSeeder::class,
            SchoolSeeder::class,
            AcademicYearSeeder::class,
            ClassSeeder::class,
            StudentSeeder::class,
            StudentClassHistorySeeder::class,
            UserSeeder::class,
            TeacherSeeder::class,
            IncomeTypeSeeder::class,
            TariffSeeder::class,
            VirtualAccountSeeder::class,
            AuditorRoleSeeder::class,
            VerifyAllUsersSeeder::class,
            InvoiceSeeder::class,
            ReceiptSeeder::class,
        ]);
    }
}
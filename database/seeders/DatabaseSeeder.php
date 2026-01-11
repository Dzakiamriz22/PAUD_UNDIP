<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
            SuperadminSeeder::class,
            SchoolSeeder::class,
            AcademicYearSeeder::class,
            ClassSeeder::class,
            StudentSeeder::class,
            StudentClassHistorySeeder::class,
        ]);
    }
}
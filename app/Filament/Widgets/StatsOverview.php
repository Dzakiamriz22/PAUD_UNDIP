<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Student;
use App\Models\SchoolClass;
use App\Models\Receipt;

class StatsOverview extends BaseWidget
{
    protected ?string $heading = 'Statistik PAUD';

    protected function getStats(): array
    {
        $totalStudents = Student::count();
        $totalClasses = SchoolClass::count();
        $totalIncome = (float) Receipt::sum('amount_paid');

        return [
            Stat::make('Total Siswa', (string) $totalStudents)
                ->description('Siswa terdaftar')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('primary')
                ->chart([]),

            Stat::make('Total Kelas', (string) $totalClasses)
                ->description('Kelas aktif')
                ->descriptionIcon('heroicon-m-academic-cap')
                ->color('success')
                ->chart([]),

            Stat::make('Total Pemasukan', 'Rp ' . number_format($totalIncome, 0, ',', '.'))
                ->description('Total penerimaan (semua waktu)')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('warning')
                ->chart([]),
        ];
    }
}

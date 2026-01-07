<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected ?string $heading = 'Statistik Aplikasi';

    protected function getStats(): array
    {
        return [
            Stat::make('Total Users', '0')
                ->description('Pengguna terdaftar')
                ->descriptionIcon('heroicon-m-users')
                ->color('primary')
                ->chart([7, 3, 4, 5, 6, 3, 5]),

            Stat::make('Total Posts', '0')
                ->description('Postingan aktif')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('success')
                ->chart([3, 2, 4, 3, 5, 4, 6]),

            Stat::make('Total Orders', '0')
                ->description('Pesanan baru')
                ->descriptionIcon('heroicon-m-shopping-cart')
                ->color('warning')
                ->chart([5, 4, 3, 6, 5, 4, 7]),
        ];
    }
}

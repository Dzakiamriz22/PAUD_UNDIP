<?php

namespace App\Filament\Pages;

use App\Filament\Resources\UserResource\Widgets\UserInfo;
use App\Filament\Widgets\AccountWidget;
use App\Filament\Widgets\ApplicationInfo;
use App\Filament\Widgets\StatsOverview;
use App\Models\Sekolah;
use Awcodes\Overlook\Widgets\OverlookWidget;
use Filament\Pages\Page;
use Filament\Widgets\FilamentInfoWidget;

class Dashboard extends \Filament\Pages\Dashboard
{
    protected static string $view = 'filament.pages.dashboard';

    protected function getHeaderWidgets(): array
    {
        return [
            ApplicationInfo::class,
            AccountWidget::class,
            // FilamentInfoWidget::class,
            StatsOverview::class,
        ];
    }
}

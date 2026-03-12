<?php

namespace App\Filament\Pages;

use App\Filament\Resources\UserResource\Widgets\UserInfo;
use App\Filament\Widgets\AccountWidget;
use App\Filament\Widgets\ApplicationInfo;
use App\Filament\Widgets\StatsOverview;
use App\Filament\Widgets\MonthlyIncomeChart;
use App\Filament\Widgets\RevenueBreakdownChart;
use App\Filament\Widgets\RevenueByProgramChart;
// Use each widget's ::make() helper to create WidgetConfiguration instances
use App\Models\Sekolah;
use Awcodes\Overlook\Widgets\OverlookWidget;
use Filament\Pages\Page;
use Filament\Widgets\FilamentInfoWidget;

class Dashboard extends \Filament\Pages\Dashboard
{
    protected static string $view = 'filament-panels::pages.dashboard';

    protected function getHeaderWidgets(): array
    {
        return [
            ApplicationInfo::class,
            AccountWidget::class,
            // keep header minimal for accounting dashboard
        ];
    }
    public function getColumns(): int | string | array
    {
        return [
            'lg' => 2,
            'md' => 2,
            'sm' => 1,
        ]; // two-column grid on large screens for side-by-side charts
    }

    public function getWidgets(): array
    {
        return [
            // Top: full-width KPIs (Ringkasan Keuangan)
            StatsOverview::make([
                'columnSpan' => ['lg' => 'full', 'md' => 'full', 'sm' => 'full'],
            ]),

            // Main comparison chart: full width for high visibility
            MonthlyIncomeChart::make([
                'columnSpan' => ['lg' => 'full', 'md' => 'full', 'sm' => 'full'],
            ]),

            // Two smaller charts side-by-side on large screens
            RevenueBreakdownChart::make([
                'columnSpan' => ['lg' => 1, 'md' => 'full', 'sm' => 'full'],
            ]),
            RevenueByProgramChart::make([
                'columnSpan' => ['lg' => 1, 'md' => 'full', 'sm' => 'full'],
            ]),
        ];
    }
}

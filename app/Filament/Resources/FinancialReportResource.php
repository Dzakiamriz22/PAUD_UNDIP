<?php

namespace App\Filament\Resources;

use App\Models\FinancialReport;
use App\Filament\Resources\FinancialReportResource\Pages;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Model;

class FinancialReportResource extends Resource
{
    protected static ?string $model = FinancialReport::class;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    protected static ?string $navigationGroup = 'Keuangan';
    protected static ?string $navigationLabel = 'Laporan Keuangan';
    protected static ?int $navigationSort = 2;

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFinancialReports::route('/'),
        ];
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->can('view_any_financial::report') ?? false;
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('view_any_financial::report') ?? false;
    }

    public static function canView(Model $record): bool
    {
        return auth()->user()?->can('view_financial::report') ?? false;
    }

    public static function getPermissionPrefixes(): array
    {
        return [
            'view',
            'view_any',
        ];
    }
}

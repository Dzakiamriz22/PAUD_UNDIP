<?php

namespace App\Filament\Resources;

use App\Models\Invoice;
use App\Filament\Resources\FinancialReportResource\Pages;
use Filament\Resources\Resource;

class FinancialReportResource extends Resource
{
    protected static ?string $model = Invoice::class;

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
        // Temporarily enable navigation visibility for testing.
        return true;
    }
}

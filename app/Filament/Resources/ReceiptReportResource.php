<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReceiptReportResource\Pages;
use App\Models\FinancialReport;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Model;

class ReceiptReportResource extends Resource
{
    protected static ?string $model = FinancialReport::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationGroup = 'Keuangan';
    protected static ?string $navigationLabel = 'Laporan Penerimaan';
    protected static ?int $navigationSort = 6;

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListReceiptReports::route('/'),
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

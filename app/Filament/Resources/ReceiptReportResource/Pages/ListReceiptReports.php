<?php

namespace App\Filament\Resources\ReceiptReportResource\Pages;

use App\Filament\Resources\ReceiptReportResource;
use App\Filament\Resources\FinancialReportResource\Pages\ListFinancialReports;

class ListReceiptReports extends ListFinancialReports
{
    protected static string $resource = ReceiptReportResource::class;
    protected static string $view = 'filament.resources.financial-report-resource.pages.list-financial-reports';

    public $reportType = 'receipt';
}

<?php

namespace App\Filament\Resources\FinancialReportResource\Pages;

use App\Filament\Resources\FinancialReportResource;

class ListRevenueReports extends ListFinancialReports
{
    protected static string $resource = FinancialReportResource::class;
    protected static string $view = 'filament.resources.financial-report-resource.pages.list-financial-reports';

    public $reportType = 'revenue';
}

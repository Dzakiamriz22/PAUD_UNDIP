<?php

namespace App\Filament\Resources\FinancialReportResource\Pages;

use App\Filament\Resources\FinancialReportResource;
use App\Models\Invoice;
use App\Models\Receipt;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\DB;

class ListFinancialReports extends ListRecords
{
    protected static string $resource = FinancialReportResource::class;
    protected static string $view = 'filament.resources.financial-report-resource.pages.list-financial-reports';

    public $totalInvoiced = 0;
    public $totalPaid = 0;
    public $totalOutstanding = 0;
    public $totalDiscounts = 0;

    // Filter properties
    public $granularity = 'monthly'; // monthly|yearly
    public $month = null; // 1-12
    public $year = null; // 4-digit

    // Computed report rows (aggregated)
    public $reportRows = [];
    public $incomeSources = [];
    public $currentPeriodTotal = 0;
    public $previousPeriodTotal = 0;
    public $periodChangePercent = 0;
    public $sparkline = []; // array of numbers for last 6 periods

    public function mount(): void
    {
        $now = now();
        $this->month = $now->month;
        $this->year = $now->year;

        // Base totals from invoices/receipts
        $this->totalInvoiced = (float) Invoice::sum('total_amount');
        $this->totalDiscounts = (float) Invoice::sum('discount_amount');
        $this->totalPaid = (float) Receipt::sum('amount_paid');
        $this->totalOutstanding = max(0, $this->totalInvoiced - $this->totalPaid);

        $this->applyFilters();
    }

    public function applyFilters(): void
    {
        $query = DB::table('receipts')
            ->selectRaw(
                $this->granularity === 'monthly'
                    ? "YEAR(payment_date) as year, MONTH(payment_date) as month, SUM(amount_paid) as total_amount, COUNT(*) as count"
                    : "YEAR(payment_date) as year, SUM(amount_paid) as total_amount, COUNT(*) as count"
            )
            ->groupBy($this->granularity === 'monthly' ? ['year', 'month'] : ['year'])
            ->orderBy('year', 'desc')
            ->orderBy($this->granularity === 'monthly' ? 'month' : 'year', 'desc');

        if ($this->granularity === 'monthly') {
            $query->whereYear('payment_date', $this->year);
            // optional: filter by month if set
            if (! empty($this->month)) {
                $query->whereMonth('payment_date', $this->month);
            }
        } else {
            if (! empty($this->year)) {
                $query->whereYear('payment_date', $this->year);
            }
        }

        $rows = $query->get()->map(function ($r) {
            return [
                'year' => $r->year,
                'month' => $r->month ?? null,
                'total_amount' => (float) $r->total_amount,
                'count' => (int) $r->count,
            ];
        })->toArray();

        $this->reportRows = $rows;

        // compute current period total (sum of visible rows)
        $this->currentPeriodTotal = array_sum(array_column($rows, 'total_amount'));

        // previous period total: for monthly -> previous month, yearly -> previous year
        if ($this->granularity === 'monthly') {
            $prev = DB::table('receipts')
                ->selectRaw('SUM(amount_paid) as total')
                ->whereYear('payment_date', $this->month == 1 ? $this->year - 1 : $this->year)
                ->whereMonth('payment_date', $this->month == 1 ? 12 : max(1, $this->month - 1))
                ->value('total');
        } else {
            $prev = DB::table('receipts')
                ->selectRaw('SUM(amount_paid) as total')
                ->whereYear('payment_date', $this->year - 1)
                ->value('total');
        }

        $this->previousPeriodTotal = (float) ($prev ?? 0);
        $this->periodChangePercent = $this->previousPeriodTotal > 0
            ? round((($this->currentPeriodTotal - $this->previousPeriodTotal) / $this->previousPeriodTotal) * 100, 2)
            : ($this->currentPeriodTotal > 0 ? 100.0 : 0.0);

        // sparkline: last 6 months (or years) totals
        $this->sparkline = [];
        if ($this->granularity === 'monthly') {
            for ($i = 5; $i >= 0; $i--) {
                $dt = now()->setYear($this->year)->subMonths($i);
                $val = DB::table('receipts')
                    ->whereYear('payment_date', $dt->year)
                    ->whereMonth('payment_date', $dt->month)
                    ->sum('amount_paid');
                $this->sparkline[] = (float) $val;
            }
        } else {
            for ($i = 5; $i >= 0; $i--) {
                $yr = $this->year - $i;
                $val = DB::table('receipts')
                    ->whereYear('payment_date', $yr)
                    ->sum('amount_paid');
                $this->sparkline[] = (float) $val;
            }
        }

        // Compute income source breakdown for the filtered receipts
        $receiptBase = DB::table('receipts');

        if ($this->granularity === 'monthly') {
            $receiptBase->whereYear('payment_date', $this->year);
            if (! empty($this->month)) {
                $receiptBase->whereMonth('payment_date', $this->month);
            }
        } else {
            if (! empty($this->year)) {
                $receiptBase->whereYear('payment_date', $this->year);
            }
        }

        $invoiceIds = $receiptBase->pluck('invoice_id')->unique()->filter()->values()->toArray();

        if (empty($invoiceIds)) {
            $this->incomeSources = [];
            return;
        }

        $sources = DB::table('invoice_items')
            ->join('tariffs', 'invoice_items.tariff_id', '=', 'tariffs.id')
            ->join('income_types', 'tariffs.income_type_id', '=', 'income_types.id')
            ->whereIn('invoice_items.invoice_id', $invoiceIds)
            ->where('income_types.is_discount', false)
            ->selectRaw('income_types.name as income_type, SUM(invoice_items.final_amount) as total_amount, COUNT(*) as items_count')
            ->groupBy('income_types.name')
            ->orderByDesc('total_amount')
            ->get()
            ->map(function ($s) {
                return [
                    'income_type' => $s->income_type,
                    'total_amount' => (float) $s->total_amount,
                    'items_count' => (int) $s->items_count,
                ];
            })->toArray();

        $this->incomeSources = $sources;
    }
}

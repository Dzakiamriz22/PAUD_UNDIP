<?php

namespace App\Filament\Resources\FinancialReportResource\Pages;

use App\Exports\FinancialReportExport;
use App\Filament\Resources\FinancialReportResource;
use App\Models\Invoice;
use App\Models\Receipt;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;

class ListFinancialReports extends ListRecords
{
    protected static string $resource = FinancialReportResource::class;
    protected static string $view = 'filament.resources.financial-report-resource.pages.list-financial-reports';

    public $totalInvoiced = 0;
    public $totalPaid = 0;
    public $totalOutstanding = 0;
    public $totalDiscounts = 0;
    public $averageTransactionValue = 0;
    public $transactionCount = 0;
    public $collectionRate = 0; // Percentage of invoiced amount that has been paid

    // Filter properties
    public $granularity = 'monthly'; // monthly|yearly
    public $month = null; // 1-12
    public $year = null; // 4-digit

    // Computed report rows (aggregated)
    public $reportRows = [];
    public $incomeSources = [];
    public $collectionByClass = []; // collection summary by class
    public $currentPeriodTotal = 0;
    public $previousPeriodTotal = 0;
    public $periodChangePercent = 0;
    public $sparkline = []; // array of numbers for last 6 periods
    public $monthlyComparison = []; // Compare current year months

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
        
        // Calculate additional metrics
        $this->transactionCount = Receipt::count();
        $this->averageTransactionValue = $this->transactionCount > 0 
            ? $this->totalPaid / $this->transactionCount 
            : 0;
        $this->collectionRate = $this->totalInvoiced > 0 
            ? ($this->totalPaid / $this->totalInvoiced) * 100 
            : 0;

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
        
        // Get collection summary by class
        $classQuery = DB::table('receipts')
            ->join('invoices', 'receipts.invoice_id', '=', 'invoices.id')
            ->join('students', 'invoices.student_id', '=', 'students.id')
            ->join('student_class_histories', 'students.id', '=', 'student_class_histories.student_id')
            ->join('classes', 'student_class_histories.class_id', '=', 'classes.id');
        
        if ($this->granularity === 'monthly') {
            $classQuery->whereYear('receipts.payment_date', $this->year);
            if (! empty($this->month)) {
                $classQuery->whereMonth('receipts.payment_date', $this->month);
            }
        } else {
            if (! empty($this->year)) {
                $classQuery->whereYear('receipts.payment_date', $this->year);
            }
        }
        
        // Get invoiced amount per class
        $invoiceQuery = DB::table('invoices')
            ->join('students', 'invoices.student_id', '=', 'students.id')
            ->join('student_class_histories', 'students.id', '=', 'student_class_histories.student_id')
            ->join('classes', 'student_class_histories.class_id', '=', 'classes.id');
        
        if ($this->granularity === 'monthly') {
            $invoiceQuery->whereYear('invoices.created_at', $this->year);
            if (! empty($this->month)) {
                $invoiceQuery->whereMonth('invoices.created_at', $this->month);
            }
        } else {
            if (! empty($this->year)) {
                $invoiceQuery->whereYear('invoices.created_at', $this->year);
            }
        }
        
        $collectionData = $classQuery
            ->selectRaw('classes.code, SUM(receipts.amount_paid) as total_paid, COUNT(DISTINCT invoices.id) as invoice_count')
            ->groupBy('classes.id', 'classes.code')
            ->get()
            ->keyBy('code')
            ->toArray();
        
        $invoiceData = $invoiceQuery
            ->selectRaw('classes.code, SUM(invoices.total_amount) as total_invoiced, COUNT(DISTINCT students.id) as student_count')
            ->groupBy('classes.id', 'classes.code')
            ->get()
            ->keyBy('code')
            ->toArray();
        
        $this->collectionByClass = [];
        foreach ($invoiceData as $classCode => $data) {
            $totalInvoiced = (float) $data->total_invoiced;
            $totalPaid = isset($collectionData[$classCode]) ? (float) $collectionData[$classCode]->total_paid : 0;
            $collectionRate = $totalInvoiced > 0 ? ($totalPaid / $totalInvoiced) * 100 : 0;
            
            $this->collectionByClass[] = [
                'class_name' => $classCode,
                'student_count' => (int) $data->student_count,
                'total_invoiced' => $totalInvoiced,
                'total_paid' => $totalPaid,
                'outstanding' => max(0, $totalInvoiced - $totalPaid),
                'collection_rate' => (float) $collectionRate,
            ];
        }
        
        // Sort by class name
        usort($this->collectionByClass, function($a, $b) {
            return strcmp($a['class_name'], $b['class_name']);
        });
        
        // Monthly comparison for current year
        if ($this->granularity === 'monthly') {
            $this->monthlyComparison = [];
            for ($m = 1; $m <= 12; $m++) {
                $total = DB::table('receipts')
                    ->whereYear('payment_date', $this->year)
                    ->whereMonth('payment_date', $m)
                    ->sum('amount_paid');
                $this->monthlyComparison[] = [
                    'month' => $m,
                    'month_name' => \DateTime::createFromFormat('!m', $m)->format('M'),
                    'total' => (float) $total,
                ];
            }
        }
    }
    
    public function exportPdf()
    {
        $data = [
            'totalInvoiced' => $this->totalInvoiced,
            'totalPaid' => $this->totalPaid,
            'totalOutstanding' => $this->totalOutstanding,
            'totalDiscounts' => $this->totalDiscounts,
            'averageTransactionValue' => $this->averageTransactionValue,
            'transactionCount' => $this->transactionCount,
            'collectionRate' => $this->collectionRate,
            'currentPeriodTotal' => $this->currentPeriodTotal,
            'previousPeriodTotal' => $this->previousPeriodTotal,
            'periodChangePercent' => $this->periodChangePercent,
            'reportRows' => $this->reportRows,
            'incomeSources' => $this->incomeSources,
            'collectionByClass' => $this->collectionByClass,
            'granularity' => $this->granularity,
            'month' => $this->month,
            'year' => $this->year,
            'generatedAt' => now(),
        ];
        
        $pdf = Pdf::loadView('pdf.financial-report', $data)
            ->setPaper('A4', 'landscape');
            
        $filename = 'laporan-keuangan-' . $this->year;
        if ($this->granularity === 'monthly' && $this->month) {
            $filename .= '-' . str_pad($this->month, 2, '0', STR_PAD_LEFT);
        }
        $filename .= '.pdf';
        
        return response()->streamDownload(function() use ($pdf) {
            echo $pdf->output();
        }, $filename);
    }
    
    public function exportExcel()
    {
        $filename = 'laporan-keuangan-' . $this->year;
        if ($this->granularity === 'monthly' && $this->month) {
            $filename .= '-' . str_pad($this->month, 2, '0', STR_PAD_LEFT);
        }
        $filename .= '.xlsx';

        $summary = [
            'Periode' => $this->granularity === 'monthly' ? 'Bulanan' : 'Tahunan',
            'Tahun' => $this->year,
        ];

        if ($this->granularity === 'monthly' && $this->month) {
            $summary['Bulan'] = \DateTime::createFromFormat('!m', $this->month)->format('F');
        }

        $summary['Total Tagihan'] = (float) $this->totalInvoiced;
        $summary['Total Pembayaran'] = (float) $this->totalPaid;
        $summary['Total Outstanding'] = (float) $this->totalOutstanding;
        $summary['Total Diskon'] = (float) $this->totalDiscounts;
        $summary['Rata-rata Transaksi'] = (float) $this->averageTransactionValue;
        $summary['Jumlah Transaksi'] = (int) $this->transactionCount;
        $summary['Tingkat Koleksi'] = number_format($this->collectionRate, 2) . '%';
        $summary['Total Pembayaran Periode Ini'] = (float) $this->currentPeriodTotal;
        $summary['Total Pembayaran Periode Sebelumnya'] = (float) $this->previousPeriodTotal;
        $summary['Perubahan Periode'] = number_format($this->periodChangePercent, 2) . '%';
        $summary['Waktu Export'] = now()->format('Y-m-d H:i');

        $export = new FinancialReportExport(
            $this->granularity,
            $this->month,
            $this->year,
            $summary,
            $this->reportRows,
            $this->incomeSources,
            $this->collectionByClass
        );

        return Excel::download($export, $filename);
    }
}

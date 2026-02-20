<?php

namespace App\Filament\Resources\FinancialReportResource\Pages;

use App\Exports\FinancialReportExport;
use App\Filament\Resources\FinancialReportResource;
use App\Models\Invoice;
use App\Models\Receipt;
use App\Settings\GeneralSettings;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Carbon;
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
    public $incomeTypeId = null; // Filter by income type
    public $classId = null; // Filter by class
    public $status = 'all'; // all|paid|unpaid|partial
    public $academicYearId = null; // Filter by academic year

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
        
        // Set default academic year to active one
        $activeAcademicYear = \App\Models\AcademicYear::where('is_active', true)->first();
        if ($activeAcademicYear) {
            $this->academicYearId = $activeAcademicYear->id;
        }

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

    // Computed properties for filter dropdowns (cached automatically by Livewire)
    // These queries are only executed once per request and cached
    public function getIncomeTypesProperty()
    {
        return \App\Models\IncomeType::orderBy('name')->get();
    }

    public function getClassesProperty()
    {
        return \App\Models\SchoolClass::orderBy('code')->get();
    }

    public function getAcademicYearsProperty()
    {
        return \App\Models\AcademicYear::orderByDesc('year')
            ->orderByDesc('semester')
            ->get();
    }
    
    /**
     * Public method to apply filters - called by wire:click
     * This ensures Livewire properly tracks the state update
     */
    public function applyFiltersAction()
    {
        $this->applyFilters();
        
        // Force Livewire to re-render
        $this->dispatch('filters-applied');
    }
    
    /**
     * Reset filters to default
     */
    public function resetFilters()
    {
        $this->granularity = 'monthly';
        $this->month = now()->month;
        $this->year = now()->year;
        $this->incomeTypeId = null;
        $this->classId = null;
        $this->status = 'all';
        
        // Reset to active academic year
        $activeAcademicYear = \App\Models\AcademicYear::where('is_active', true)->first();
        if ($activeAcademicYear) {
            $this->academicYearId = $activeAcademicYear->id;
        }
        
        $this->applyFilters();
    }

    /**
     * Apply filters and compute report data
     * 
     * Performance tips:
     * - Ensure indexes on: receipts.payment_date, invoices.issued_at, invoices.academic_year_id
     * - Ensure indexes on: student_class_histories.class_id, student_class_histories.is_active
     * - Consider adding composite indexes for frequently joined columns
     */
    public function applyFilters(): void
    {
        // Base query for receipts with all filter dimensions (except income type which is handled separately)
        $query = DB::table('receipts')
            ->join('invoices', 'receipts.invoice_id', '=', 'invoices.id')
            ->join('students', 'invoices.student_id', '=', 'students.id')
            ->leftJoin('student_class_histories as sch', function($join) {
                $join->on('students.id', '=', 'sch.student_id')
                    ->where('sch.is_active', true);
            })
            ->selectRaw(
                $this->granularity === 'monthly'
                    ? "YEAR(receipts.payment_date) as year, MONTH(receipts.payment_date) as month, SUM(receipts.amount_paid) as total_amount, COUNT(receipts.id) as count"
                    : "YEAR(receipts.payment_date) as year, SUM(receipts.amount_paid) as total_amount, COUNT(receipts.id) as count"
            );

        // Apply date filters
        if ($this->granularity === 'monthly') {
            $query->whereYear('receipts.payment_date', $this->year);
            if (! empty($this->month)) {
                $query->whereMonth('receipts.payment_date', $this->month);
            }
        } else {
            if (! empty($this->year)) {
                $query->whereYear('receipts.payment_date', $this->year);
            }
        }

        // Apply category filters
        if ($this->academicYearId) {
            $query->where('invoices.academic_year_id', $this->academicYearId);
        }
        
        if ($this->classId) {
            $query->where('sch.class_id', $this->classId);
        }
        
        if ($this->status !== 'all') {
            $query->where('invoices.status', $this->status);
        }

        // Clone query for income type filtering
        $baseQuery = clone $query;
        
        // Group by period
        $query->groupBy($this->granularity === 'monthly' ? ['year', 'month'] : ['year'])
            ->orderBy('year', 'desc');
        
        if ($this->granularity === 'monthly') {
            $query->orderBy('month', 'desc');
        }

        $rows = $query->get();
        
        // Filter by income type if set - use WHERE IN subquery approach
        if ($this->incomeTypeId) {
            // Get receipt IDs that have this income type
            $receiptIdsQuery = DB::table('receipts')
                ->join('invoices', 'receipts.invoice_id', '=', 'invoices.id')
                ->join('students', 'invoices.student_id', '=', 'students.id')
                ->join('invoice_items', 'invoices.id', '=', 'invoice_items.invoice_id')
                ->join('tariffs', 'invoice_items.tariff_id', '=', 'tariffs.id')
                ->leftJoin('student_class_histories as sch', function($join) {
                    $join->on('students.id', '=', 'sch.student_id')
                        ->where('sch.is_active', true);
                })
                ->where('tariffs.income_type_id', $this->incomeTypeId);
            
            // Apply same category filters
            if ($this->academicYearId) {
                $receiptIdsQuery->where('invoices.academic_year_id', $this->academicYearId);
            }
            if ($this->classId) {
                $receiptIdsQuery->where('sch.class_id', $this->classId);
            }
            if ($this->status !== 'all') {
                $receiptIdsQuery->where('invoices.status', $this->status);
            }
            
            // Apply date filters
            if ($this->granularity === 'monthly') {
                $receiptIdsQuery->whereYear('receipts.payment_date', $this->year);
                if (! empty($this->month)) {
                    $receiptIdsQuery->whereMonth('receipts.payment_date', $this->month);
                }
            } else {
                if (! empty($this->year)) {
                    $receiptIdsQuery->whereYear('receipts.payment_date', $this->year);
                }
            }
            
            $validReceiptIds = $receiptIdsQuery->pluck('receipts.id')->toArray();
            
            // Re-aggregate using only valid receipts
            if (!empty($validReceiptIds)) {
                $filteredQuery = DB::table('receipts')
                    ->whereIn('id', $validReceiptIds)
                    ->selectRaw(
                        $this->granularity === 'monthly'
                            ? "YEAR(payment_date) as year, MONTH(payment_date) as month, SUM(amount_paid) as total_amount, COUNT(id) as count"
                            : "YEAR(payment_date) as year, SUM(amount_paid) as total_amount, COUNT(id) as count"
                    )
                    ->groupBy($this->granularity === 'monthly' ? ['year', 'month'] : ['year'])
                    ->orderBy('year', 'desc');
                
                if ($this->granularity === 'monthly') {
                    $filteredQuery->orderBy('month', 'desc');
                }
                
                $rows = $filteredQuery->get();
            } else {
                $rows = collect();
            }
        }

        $rows = $rows->map(function ($r) {
            // Get detailed transactions for this period
            $detailsQuery = DB::table('receipts')
                ->join('invoices', 'receipts.invoice_id', '=', 'invoices.id')
                ->join('students', 'invoices.student_id', '=', 'students.id')
                ->leftJoin('student_class_histories as sch', function($join) {
                    $join->on('students.id', '=', 'sch.student_id')
                        ->where('sch.is_active', true);
                })
                ->leftJoin('classes', 'sch.class_id', '=', 'classes.id');
            
            // Apply same filters as main query
            if ($this->granularity === 'monthly') {
                $detailsQuery->whereYear('receipts.payment_date', $r->year)
                    ->whereMonth('receipts.payment_date', $r->month);
            } else {
                $detailsQuery->whereYear('receipts.payment_date', $r->year);
            }
            
            if ($this->academicYearId) {
                $detailsQuery->where('invoices.academic_year_id', $this->academicYearId);
            }
            
            if ($this->classId) {
                $detailsQuery->where('sch.class_id', $this->classId);
            }
            
            if ($this->status !== 'all') {
                $detailsQuery->where('invoices.status', $this->status);
            }
            
            // Apply income type filter if set
            if ($this->incomeTypeId) {
                $detailsQuery->join('invoice_items', 'invoices.id', '=', 'invoice_items.invoice_id')
                    ->join('tariffs', 'invoice_items.tariff_id', '=', 'tariffs.id')
                    ->where('tariffs.income_type_id', $this->incomeTypeId);
            }
            
            $details = $detailsQuery
                ->select(
                    'receipts.id',
                    'receipts.receipt_number',
                    'receipts.payment_date',
                    'receipts.amount_paid',
                    'students.name as student_name',
                    'classes.code as class_code',
                    'invoices.invoice_number'
                )
                ->orderBy('receipts.payment_date', 'desc')
                ->get()
                ->map(function ($d) {
                    return [
                        'receipt_number' => $d->receipt_number,
                        'payment_date' => $d->payment_date,
                        'amount_paid' => (float) $d->amount_paid,
                        'student_name' => $d->student_name,
                        'class_code' => $d->class_code,
                        'invoice_number' => $d->invoice_number,
                    ];
                })
                ->toArray();
            
            return [
                'year' => $r->year,
                'month' => $r->month ?? null,
                'total_amount' => (float) $r->total_amount,
                'count' => (int) $r->count,
                'details' => $details,
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
        $receiptBase = DB::table('receipts')
            ->join('invoices', 'receipts.invoice_id', '=', 'invoices.id')
            ->join('students', 'invoices.student_id', '=', 'students.id')
            ->leftJoin('student_class_histories as sch', function($join) {
                $join->on('students.id', '=', 'sch.student_id')
                    ->where('sch.is_active', true);
            });

        // Apply date filters
        if ($this->granularity === 'monthly') {
            $receiptBase->whereYear('receipts.payment_date', $this->year);
            if (! empty($this->month)) {
                $receiptBase->whereMonth('receipts.payment_date', $this->month);
            }
        } else {
            if (! empty($this->year)) {
                $receiptBase->whereYear('receipts.payment_date', $this->year);
            }
        }

        // Apply category filters
        if ($this->academicYearId) {
            $receiptBase->where('invoices.academic_year_id', $this->academicYearId);
        }
        
        if ($this->classId) {
            $receiptBase->where('sch.class_id', $this->classId);
        }
        
        if ($this->status !== 'all') {
            $receiptBase->where('invoices.status', $this->status);
        }
        
        // Apply income type filter if set
        if ($this->incomeTypeId) {
            $receiptBase->join('invoice_items', 'invoices.id', '=', 'invoice_items.invoice_id')
                ->join('tariffs', 'invoice_items.tariff_id', '=', 'tariffs.id')
                ->where('tariffs.income_type_id', $this->incomeTypeId);
        }

        $invoiceIds = $receiptBase->pluck('receipts.invoice_id')->unique()->filter()->values()->toArray();

        if (empty($invoiceIds)) {
            $this->incomeSources = [];
            $this->collectionByClass = [];
            return;
        }

        // Build income sources query with income type filter
        $sourcesQuery = DB::table('invoice_items')
            ->join('tariffs', 'invoice_items.tariff_id', '=', 'tariffs.id')
            ->join('income_types', 'tariffs.income_type_id', '=', 'income_types.id')
            ->whereIn('invoice_items.invoice_id', $invoiceIds)
            ->where('income_types.is_discount', false);
        
        // Apply income type filter if set
        if ($this->incomeTypeId) {
            $sourcesQuery->where('income_types.id', $this->incomeTypeId);
        }

        $sources = $sourcesQuery
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
        
        // Build valid receipt IDs for collection by class (with all filters applied)
        $validReceiptIdsQuery = DB::table('receipts')
            ->join('invoices', 'receipts.invoice_id', '=', 'invoices.id')
            ->join('students', 'invoices.student_id', '=', 'students.id')
            ->join('student_class_histories', function($join) {
                $join->on('students.id', '=', 'student_class_histories.student_id')
                    ->where('student_class_histories.is_active', true);
            });
        
        // Apply date filters
        if ($this->granularity === 'monthly') {
            $validReceiptIdsQuery->whereYear('receipts.payment_date', $this->year);
            if (! empty($this->month)) {
                $validReceiptIdsQuery->whereMonth('receipts.payment_date', $this->month);
            }
        } else {
            if (! empty($this->year)) {
                $validReceiptIdsQuery->whereYear('receipts.payment_date', $this->year);
            }
        }
        
        // Apply category filters
        if ($this->academicYearId) {
            $validReceiptIdsQuery->where('invoices.academic_year_id', $this->academicYearId);
        }
        
        if ($this->classId) {
            $validReceiptIdsQuery->where('student_class_histories.class_id', $this->classId);
        }
        
        if ($this->status !== 'all') {
            $validReceiptIdsQuery->where('invoices.status', $this->status);
        }
        
        // Apply income type filter if set
        if ($this->incomeTypeId) {
            $validReceiptIdsQuery->join('invoice_items', 'invoices.id', '=', 'invoice_items.invoice_id')
                ->join('tariffs', 'invoice_items.tariff_id', '=', 'tariffs.id')
                ->where('tariffs.income_type_id', $this->incomeTypeId);
        }
        
        $validReceiptIds = $validReceiptIdsQuery->pluck('receipts.id')->unique()->toArray();
        
        if (empty($validReceiptIds)) {
            $this->collectionByClass = [];
            return;
        }
        
        // Get collection summary by class (using pre-filtered receipt IDs)
        $collectionData = DB::table('receipts')
            ->join('invoices', 'receipts.invoice_id', '=', 'invoices.id')
            ->join('students', 'invoices.student_id', '=', 'students.id')
            ->join('student_class_histories', function($join) {
                $join->on('students.id', '=', 'student_class_histories.student_id')
                    ->where('student_class_histories.is_active', true);
            })
            ->join('classes', 'student_class_histories.class_id', '=', 'classes.id')
            ->whereIn('receipts.id', $validReceiptIds)
            ->selectRaw('classes.code, SUM(receipts.amount_paid) as total_paid')
            ->groupBy('classes.code')
            ->get()
            ->keyBy('code')
            ->toArray();
        
        // Build valid invoice IDs for invoicing summary (with all filters applied)
        $validInvoiceIdsQuery = DB::table('invoices')
            ->join('students', 'invoices.student_id', '=', 'students.id')
            ->join('student_class_histories', function($join) {
                $join->on('students.id', '=', 'student_class_histories.student_id')
                    ->where('student_class_histories.is_active', true);
            });
        
        // Apply date filters
        if ($this->granularity === 'monthly') {
            $validInvoiceIdsQuery->whereYear('invoices.created_at', $this->year);
            if (! empty($this->month)) {
                $validInvoiceIdsQuery->whereMonth('invoices.created_at', $this->month);
            }
        } else {
            if (! empty($this->year)) {
                $validInvoiceIdsQuery->whereYear('invoices.created_at', $this->year);
            }
        }
        
        // Apply category filters
        if ($this->academicYearId) {
            $validInvoiceIdsQuery->where('invoices.academic_year_id', $this->academicYearId);
        }
        
        if ($this->classId) {
            $validInvoiceIdsQuery->where('student_class_histories.class_id', $this->classId);
        }
        
        if ($this->status !== 'all') {
            $validInvoiceIdsQuery->where('invoices.status', $this->status);
        }
        
        // Apply income type filter if set
        if ($this->incomeTypeId) {
            $validInvoiceIdsQuery->join('invoice_items', 'invoices.id', '=', 'invoice_items.invoice_id')
                ->join('tariffs', 'invoice_items.tariff_id', '=', 'tariffs.id')
                ->where('tariffs.income_type_id', $this->incomeTypeId);
        }
        
        $validInvoiceIds = $validInvoiceIdsQuery->pluck('invoices.id')->unique()->toArray();
        
        // Get invoiced amount per class (using pre-filtered invoice IDs)
        $invoiceData = DB::table('invoices')
            ->join('students', 'invoices.student_id', '=', 'students.id')
            ->join('student_class_histories', function($join) {
                $join->on('students.id', '=', 'student_class_histories.student_id')
                    ->where('student_class_histories.is_active', true);
            })
            ->join('classes', 'student_class_histories.class_id', '=', 'classes.id')
            ->whereIn('invoices.id', $validInvoiceIds)
            ->selectRaw('classes.code, SUM(invoices.total_amount) as total_invoiced')
            ->groupBy('classes.code')
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
        $generalSettings = app(GeneralSettings::class);
        $periodRange = $this->buildPeriodRange();
        $revenueRows = $this->buildRevenueRows();
        $receiptRows = $this->buildReceiptRows();

        $revenueTotal = array_sum(array_column($revenueRows, 'nominal'));
        $receiptTotals = [
            'total_tagihan' => array_sum(array_column($receiptRows, 'nilai_tagihan')),
            'total_pembayaran' => array_sum(array_column($receiptRows, 'pembayaran')),
        ];
        
        // Get filter display values
        $incomeTypeName = 'All';
        if ($this->incomeTypeId) {
            $incomeType = \App\Models\IncomeType::find($this->incomeTypeId);
            $incomeTypeName = $incomeType ? $incomeType->name : 'All';
        }
        
        $className = 'All';
        if ($this->classId) {
            $class = \App\Models\SchoolClass::find($this->classId);
            $className = $class ? $class->code : 'All';
        }
        
        $statusLabel = match($this->status) {
            'paid' => 'Lunas',
            'unpaid' => 'Belum Bayar',
            'partial' => 'Sebagian',
            default => 'All [Belum Bayar/Lunas/Refund]'
        };
        
        $academicYearLabel = $this->year;
        if ($this->academicYearId) {
            $academicYear = \App\Models\AcademicYear::find($this->academicYearId);
            $academicYearLabel = $academicYear ? $academicYear->label : $this->year;
        }

        $data = [
            'filters' => [
                'unit_usaha' => $generalSettings->school_name ?: config('app.name', 'PAUD Permata Undip'),
                'jenis_pendapatan' => $incomeTypeName,
                'kategori' => $this->granularity === 'monthly' ? '[SPP/Non SPP]' : '[SPP/Non SPP]',
                'kelas' => $className,
                'periode_transaksi' => $periodRange,
                'status' => $statusLabel,
                'tahun_anggaran' => $academicYearLabel,
                'tanggal_cetak' => now()->format('d/m/Y'),
                'sistem_bayar' => 'Auto system',
                'bendahara' => auth()->user()?->name ?? '-',
            ],
            'revenueRows' => $revenueRows,
            'receiptRows' => $receiptRows,
            'revenueTotal' => $revenueTotal,
            'receiptTotals' => $receiptTotals,
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

    private function buildPeriodRange(): string
    {
        if ($this->granularity === 'monthly' && $this->month) {
            $start = Carbon::create($this->year, $this->month, 1)->startOfDay();
            $end = Carbon::create($this->year, $this->month, 1)->endOfMonth()->endOfDay();
        } else {
            $start = Carbon::create($this->year, 1, 1)->startOfDay();
            $end = Carbon::create($this->year, 12, 31)->endOfDay();
        }

        return $start->format('d/m/Y') . ' - ' . $end->format('d/m/Y');
    }

    private function buildRevenueRows(): array
    {
        $query = DB::table('invoices')
            ->join('students', 'invoices.student_id', '=', 'students.id')
            ->leftJoin('invoice_items', 'invoices.id', '=', 'invoice_items.invoice_id')
            ->leftJoin('tariffs', 'invoice_items.tariff_id', '=', 'tariffs.id')
            ->leftJoin('student_class_histories as sch', function($join) {
                $join->on('students.id', '=', 'sch.student_id')
                    ->where('sch.is_active', true);
            })
            ->select([
                'invoices.id',
                'invoices.issued_at',
                'invoices.invoice_number',
                'students.name as student_name',
                'invoices.due_date',
                'invoices.total_amount',
                'invoices.status',
                DB::raw("GROUP_CONCAT(DISTINCT invoice_items.description SEPARATOR '; ') as description"),
                DB::raw("GROUP_CONCAT(DISTINCT tariffs.income_type_id) as income_type_ids"),
                'sch.class_id',
            ])
            ->groupBy(
                'invoices.id',
                'invoices.issued_at',
                'invoices.invoice_number',
                'students.name',
                'invoices.due_date',
                'invoices.total_amount',
                'invoices.status',
                'sch.class_id'
            )
            ->orderBy('invoices.issued_at');

        // Apply filters
        if ($this->granularity === 'monthly') {
            $query->whereYear('invoices.issued_at', $this->year);
            if (! empty($this->month)) {
                $query->whereMonth('invoices.issued_at', $this->month);
            }
        } else {
            if (! empty($this->year)) {
                $query->whereYear('invoices.issued_at', $this->year);
            }
        }
        
        if ($this->academicYearId) {
            $query->where('invoices.academic_year_id', $this->academicYearId);
        }
        
        if ($this->classId) {
            $query->where('sch.class_id', $this->classId);
        }
        
        if ($this->status !== 'all') {
            if ($this->status === 'paid') {
                $query->where('invoices.status', 'paid');
            } elseif ($this->status === 'unpaid') {
                $query->where('invoices.status', 'unpaid');
            } elseif ($this->status === 'partial') {
                $query->where('invoices.status', 'partial');
            }
        }

        $rows = $query->get();
        
        // Filter by income type if set
        if ($this->incomeTypeId) {
            $rows = $rows->filter(function($row) {
                if (!$row->income_type_ids) return false;
                $typeIds = explode(',', $row->income_type_ids);
                return in_array($this->incomeTypeId, $typeIds);
            });
        }

        return $rows->map(function ($row) {
            return [
                'tanggal_invoice' => $row->issued_at ? Carbon::parse($row->issued_at) : null,
                'nomor_invoice' => $row->invoice_number,
                'pelanggan' => $row->student_name,
                'jatuh_tempo' => $row->due_date ? Carbon::parse($row->due_date) : null,
                'nominal' => (float) $row->total_amount,
                'deskripsi' => $row->description
                ? preg_replace('/\s*\(\d+-\d+\)/', '', $row->description)
                : '-',
                'status' => $row->status,
            ];
        })->values()->toArray();
    }

    private function buildReceiptRows(): array
    {
        $query = DB::table('receipts')
            ->join('invoices', 'receipts.invoice_id', '=', 'invoices.id')
            ->join('students', 'invoices.student_id', '=', 'students.id')
            ->leftJoin('invoice_items', 'invoices.id', '=', 'invoice_items.invoice_id')
            ->leftJoin('tariffs', 'invoice_items.tariff_id', '=', 'tariffs.id')
            ->leftJoin('student_class_histories as sch', function($join) {
                $join->on('students.id', '=', 'sch.student_id')
                    ->where('sch.is_active', true);
            })
            ->select([
                'receipts.id',
                'receipts.payment_date',
                'receipts.receipt_number',
                'students.name as student_name',
                'invoices.total_amount',
                'invoices.status',
                'receipts.amount_paid',
                DB::raw("GROUP_CONCAT(DISTINCT invoice_items.description SEPARATOR '; ') as description"),
                DB::raw("GROUP_CONCAT(DISTINCT tariffs.income_type_id) as income_type_ids"),
                'sch.class_id',
            ])
            ->groupBy(
                'receipts.id',
                'receipts.payment_date',
                'receipts.receipt_number',
                'students.name',
                'invoices.total_amount',
                'invoices.status',
                'receipts.amount_paid',
                'sch.class_id'
            )
            ->orderBy('receipts.payment_date');

        // Apply filters
        if ($this->granularity === 'monthly') {
            $query->whereYear('receipts.payment_date', $this->year);
            if (! empty($this->month)) {
                $query->whereMonth('receipts.payment_date', $this->month);
            }
        } else {
            if (! empty($this->year)) {
                $query->whereYear('receipts.payment_date', $this->year);
            }
        }
        
        if ($this->academicYearId) {
            $query->where('invoices.academic_year_id', $this->academicYearId);
        }
        
        if ($this->classId) {
            $query->where('sch.class_id', $this->classId);
        }
        
        if ($this->status !== 'all') {
            if ($this->status === 'paid') {
                $query->where('invoices.status', 'paid');
            } elseif ($this->status === 'unpaid') {
                $query->where('invoices.status', 'unpaid');
            } elseif ($this->status === 'partial') {
                $query->where('invoices.status', 'partial');
            }
        }

        $rows = $query->get();
        
        // Filter by income type if set
        if ($this->incomeTypeId) {
            $rows = $rows->filter(function($row) {
                if (!$row->income_type_ids) return false;
                $typeIds = explode(',', $row->income_type_ids);
                return in_array($this->incomeTypeId, $typeIds);
            });
        }

        return $rows->map(function ($row) {
            $totalAmount = (float) $row->total_amount;
            $paidAmount = (float) $row->amount_paid;
            $status = $paidAmount >= $totalAmount ? 'Lunas' : ($paidAmount > 0 ? 'Kurang bayar' : 'Belum bayar');

            return [
                'tanggal_kuitansi' => $row->payment_date ? Carbon::parse($row->payment_date) : null,
                'nomor_kuitansi' => $row->receipt_number,
                'pelanggan' => $row->student_name,
                'nilai_tagihan' => $totalAmount,
                'pembayaran' => $paidAmount,
                'deskripsi' => $row->description
                ? preg_replace('/\s*\(\d+-\d+\)/', '', $row->description)
                : '-',
                'keterangan' => $status,
            ];
        })->values()->toArray();
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

        // Add filter information to summary
        if ($this->incomeTypeId) {
            $incomeType = \App\Models\IncomeType::find($this->incomeTypeId);
            $summary['Jenis Pendapatan'] = $incomeType ? $incomeType->name : 'Semua';
        }
        
        if ($this->classId) {
            $class = \App\Models\SchoolClass::find($this->classId);
            $summary['Kelas'] = $class ? $class->code : 'Semua';
        }
        
        if ($this->status !== 'all') {
            $summary['Status'] = match($this->status) {
                'paid' => 'Lunas',
                'unpaid' => 'Belum Bayar',
                'partial' => 'Sebagian',
                default => 'Semua'
            };
        }
        
        if ($this->academicYearId) {
            $academicYear = \App\Models\AcademicYear::find($this->academicYearId);
            $summary['Tahun Anggaran'] = $academicYear 
                ? $academicYear->year . ' - ' . ucfirst($academicYear->semester)
                : 'Semua';
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
            $this->collectionByClass,
            $this->incomeTypeId,
            $this->classId,
            $this->status,
            $this->academicYearId
        );

        return Excel::download($export, $filename);
    }
}

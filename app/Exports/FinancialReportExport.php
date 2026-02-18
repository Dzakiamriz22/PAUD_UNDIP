<?php

namespace App\Exports;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class FinancialReportExport implements WithMultipleSheets
{
    private string $granularity;
    private ?int $month;
    private ?int $year;
    private array $summary;
    private array $reportRows;
    private array $incomeSources;
    private array $collectionByClass;
    private ?string $incomeTypeId;
    private ?string $classId;
    private string $status;
    private ?string $academicYearId;

    public function __construct(
        string $granularity,
        ?int $month,
        ?int $year,
        array $summary,
        array $reportRows,
        array $incomeSources,
        array $collectionByClass,
        ?string $incomeTypeId = null,
        ?string $classId = null,
        string $status = 'all',
        ?string $academicYearId = null
    ) {
        $this->granularity = $granularity;
        $this->month = $month;
        $this->year = $year;
        $this->summary = $summary;
        $this->reportRows = $reportRows;
        $this->incomeSources = $incomeSources;
        $this->collectionByClass = $collectionByClass;
        $this->incomeTypeId = $incomeTypeId;
        $this->classId = $classId;
        $this->status = $status;
        $this->academicYearId = $academicYearId;
    }

    public function sheets(): array
    {
        $sheets = [
            new RevenueInvoiceSheet(
                $this->granularity, 
                $this->month, 
                $this->year,
                $this->incomeTypeId,
                $this->classId,
                $this->status,
                $this->academicYearId
            ),
            new ReceiptReportSheet(
                $this->granularity, 
                $this->month, 
                $this->year,
                $this->incomeTypeId,
                $this->classId,
                $this->status,
                $this->academicYearId
            ),
            new SummarySheet($this->summary),
        ];

        // For yearly reports, add monthly breakdown with transactions
        if ($this->granularity === 'yearly') {
            $sheets[] = new MonthlyTransactionSheet($this->year);
        }

        // Add class and student summaries
        $sheets[] = new CollectionByClassSheet($this->collectionByClass);
        $sheets[] = new StudentRecapSheet($this->granularity, $this->month, $this->year);
        
        // Add comprehensive detail sheet
        $sheets[] = new ComprehensiveDetailSheet($this->granularity, $this->month, $this->year);

        return $sheets;
    }
}

trait HasReportHeader
{
    protected function applyReportHeader(AfterSheet $event, string $title, string $lastColumn): void
    {
        $sheet = $event->sheet->getDelegate();

        $sheet->mergeCells("A1:{$lastColumn}1");
        $sheet->mergeCells("A2:{$lastColumn}2");
        $sheet->mergeCells("A3:{$lastColumn}3");

        $sheet->setCellValue('A1', $title);
        $sheet->setCellValue('A2', 'KEGIATAN USAHA BISNIS DAN KOMERSIAL UNIVERSITAS DIPONEGORO');
        $sheet->setCellValue('A3', 'PADA UPKAB BP UBIKAR');

        $sheet->getStyle("A1:{$lastColumn}3")
            ->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->getStyle("A1:{$lastColumn}1")->getFont()->setBold(true)->setSize(12);
        $sheet->getStyle("A2:{$lastColumn}2")->getFont()->setBold(true)->setSize(9);
        $sheet->getStyle("A3:{$lastColumn}3")->getFont()->setBold(true)->setSize(9);

        $sheet->getRowDimension(1)->setRowHeight(18);
        $sheet->getRowDimension(2)->setRowHeight(16);
        $sheet->getRowDimension(3)->setRowHeight(16);
        $sheet->getRowDimension(4)->setRowHeight(6);
    }
}

class RevenueInvoiceSheet implements FromArray, WithHeadings, WithTitle, WithColumnFormatting, ShouldAutoSize, WithEvents, WithCustomStartCell
{
    use HasReportHeader;

    private string $granularity;
    private ?int $month;
    private ?int $year;
    private ?string $incomeTypeId;
    private ?string $classId;
    private string $status;
    private ?string $academicYearId;

    public function __construct(
        string $granularity, 
        ?int $month, 
        ?int $year,
        ?string $incomeTypeId = null,
        ?string $classId = null,
        string $status = 'all',
        ?string $academicYearId = null
    ) {
        $this->granularity = $granularity;
        $this->month = $month;
        $this->year = $year;
        $this->incomeTypeId = $incomeTypeId;
        $this->classId = $classId;
        $this->status = $status;
        $this->academicYearId = $academicYearId;
    }

    public function headings(): array
    {
        return [
            'No',
            'Tanggal Invoice',
            'Nomor Invoice',
            'Pelanggan',
            'Jatuh Tempo',
            'Nominal',
            'Deskripsi',
        ];
    }

    public function array(): array
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
                'invoices.issued_at',
                'invoices.invoice_number',
                'students.name as student_name',
                'invoices.due_date',
                'invoices.total_amount',
                DB::raw("GROUP_CONCAT(DISTINCT invoice_items.description SEPARATOR '; ') as description"),
                DB::raw("GROUP_CONCAT(DISTINCT tariffs.income_type_id) as income_type_ids"),
            ])
            ->groupBy(
                'invoices.id',
                'invoices.issued_at',
                'invoices.invoice_number',
                'students.name',
                'invoices.due_date',
                'invoices.total_amount'
            )
            ->orderBy('invoices.issued_at');

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
            $query->where('invoices.status', $this->status);
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

        $data = [];
        $index = 1;
        foreach ($rows as $row) {
            $data[] = [
                $index,
                $row->issued_at ? Carbon::parse($row->issued_at) : null,
                $row->invoice_number,
                $row->student_name,
                $row->due_date ? Carbon::parse($row->due_date) : null,
                (float) $row->total_amount,
                $row->description ?: '-',
            ];
            $index++;
        }

        return $data;
    }

    public function columnFormats(): array
    {
        return [
            'B' => NumberFormat::FORMAT_DATE_YYYYMMDD2,
            'E' => NumberFormat::FORMAT_DATE_YYYYMMDD2,
            'F' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
        ];
    }

    public function title(): string
    {
        return 'Pendapatan';
    }

    public function startCell(): string
    {
        return 'A6';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $this->applyReportHeader($event, $this->title(), 'G');
            },
        ];
    }
}

class ReceiptReportSheet implements FromArray, WithHeadings, WithTitle, WithColumnFormatting, ShouldAutoSize, WithEvents, WithCustomStartCell
{
    use HasReportHeader;

    private string $granularity;
    private ?int $month;
    private ?int $year;
    private ?string $incomeTypeId;
    private ?string $classId;
    private string $status;
    private ?string $academicYearId;

    public function __construct(
        string $granularity, 
        ?int $month, 
        ?int $year,
        ?string $incomeTypeId = null,
        ?string $classId = null,
        string $status = 'all',
        ?string $academicYearId = null
    ) {
        $this->granularity = $granularity;
        $this->month = $month;
        $this->year = $year;
        $this->incomeTypeId = $incomeTypeId;
        $this->classId = $classId;
        $this->status = $status;
        $this->academicYearId = $academicYearId;
    }

    public function headings(): array
    {
        return [
            'No',
            'Tanggal Kuitansi',
            'Nomor Kuitansi',
            'Pelanggan',
            'Nilai Tagihan',
            'Pembayaran',
            'Deskripsi',
            'Keterangan',
        ];
    }

    public function array(): array
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
                'receipts.payment_date',
                'receipts.receipt_number',
                'students.name as student_name',
                'invoices.total_amount',
                'invoices.status as invoice_status',
                'receipts.amount_paid',
                DB::raw("GROUP_CONCAT(DISTINCT invoice_items.description SEPARATOR '; ') as description"),
                DB::raw("GROUP_CONCAT(DISTINCT tariffs.income_type_id) as income_type_ids"),
            ])
            ->groupBy(
                'receipts.id',
                'receipts.payment_date',
                'receipts.receipt_number',
                'students.name',
                'invoices.total_amount',
                'invoices.status',
                'receipts.amount_paid'
            )
            ->orderBy('receipts.payment_date');

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
            $query->where('invoices.status', $this->status);
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

        $data = [];
        $index = 1;
        foreach ($rows as $row) {
            $totalAmount = (float) $row->total_amount;
            $paidAmount = (float) $row->amount_paid;
            $status = $paidAmount >= $totalAmount ? 'Lunas' : ($paidAmount > 0 ? 'Kurang bayar' : 'Belum bayar');

            $data[] = [
                $index,
                $row->payment_date ? Carbon::parse($row->payment_date) : null,
                $row->receipt_number,
                $row->student_name,
                $totalAmount,
                $paidAmount,
                $row->description ?: '-',
                $status,
            ];
            $index++;
        }

        return $data;
    }

    public function columnFormats(): array
    {
        return [
            'B' => NumberFormat::FORMAT_DATE_YYYYMMDD2,
            'E' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'F' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
        ];
    }

    public function title(): string
    {
        return 'Penerimaan';
    }

    public function startCell(): string
    {
        return 'A6';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $this->applyReportHeader($event, $this->title(), 'H');
            },
        ];
    }
}

class SummarySheet implements FromArray, WithHeadings, WithTitle, ShouldAutoSize, WithEvents, WithCustomStartCell
{
    use HasReportHeader;

    private array $summary;

    public function __construct(array $summary)
    {
        $this->summary = $summary;
    }

    public function headings(): array
    {
        return ['Item', 'Nilai'];
    }

    public function array(): array
    {
        $rows = [];
        foreach ($this->summary as $label => $value) {
            $rows[] = [$label, $value];
        }

        return $rows;
    }

    public function title(): string
    {
        return 'Ringkasan';
    }

    public function startCell(): string
    {
        return 'A6';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $this->applyReportHeader($event, $this->title(), 'B');
            },
        ];
    }
}

class MonthlyTransactionSheet implements FromArray, WithHeadings, WithTitle, WithColumnFormatting, ShouldAutoSize, WithEvents, WithCustomStartCell
{
    use HasReportHeader;

    private ?int $year;

    public function __construct(?int $year)
    {
        $this->year = $year;
    }

    public function headings(): array
    {
        return [
            'Bulan',
            'Jumlah Transaksi',
            'Total Pembayaran',
            'No Invoice',
            'Nama Siswa',
            'NIS',
            'Kelas',
            'Metode Bayar',
            'Jumlah',
            'Tanggal Bayar',
            'No Referensi',
        ];
    }

    public function array(): array
    {
        $transactions = DB::table('receipts')
            ->join('invoices', 'receipts.invoice_id', '=', 'invoices.id')
            ->join('students', 'invoices.student_id', '=', 'students.id')
            ->leftJoin('student_class_histories as sch', function ($join) {
                $join->on('students.id', '=', 'sch.student_id')
                    ->where('sch.is_active', true);
            })
            ->leftJoin('classes', 'sch.class_id', '=', 'classes.id')
            ->select([
                'receipts.id',
                'receipts.payment_date',
                'receipts.receipt_number',
                'receipts.amount_paid',
                'receipts.payment_method',
                'receipts.reference_number',
                'invoices.invoice_number',
                'students.name',
                'students.nis',
                'classes.code',
            ])
            ->whereYear('receipts.payment_date', $this->year)
            ->orderBy('receipts.payment_date')
            ->get();

        $data = [];
        $monthSummary = [];

        // Group by month
        foreach ($transactions as $tx) {
            $paymentMonth = Carbon::parse($tx->payment_date)->format('m');
            $monthName = $this->getMonthName((int) $paymentMonth);

            if (!isset($monthSummary[$monthName])) {
                $monthSummary[$monthName] = [
                    'count' => 0,
                    'total' => 0,
                    'transactions' => [],
                ];
            }

            $monthSummary[$monthName]['count']++;
            $monthSummary[$monthName]['total'] += (float) $tx->amount_paid;
            $monthSummary[$monthName]['transactions'][] = $tx;
        }

        // Build rows with monthly summary first, then transaction details
        foreach ($monthSummary as $monthName => $summary) {
            $isFirstRow = true;

            foreach ($summary['transactions'] as $tx) {
                if ($isFirstRow) {
                    $data[] = [
                        $monthName,
                        $summary['count'],
                        (float) $summary['total'],
                        $tx->invoice_number,
                        $tx->name,
                        $tx->nis,
                        $tx->code ?? '-',
                        $this->formatPaymentMethod($tx->payment_method),
                        (float) $tx->amount_paid,
                        Carbon::parse($tx->payment_date),
                        $tx->reference_number,
                    ];
                    $isFirstRow = false;
                } else {
                    $data[] = [
                        '', // Month column empty for continuation
                        '',
                        '',
                        $tx->invoice_number,
                        $tx->name,
                        $tx->nis,
                        $tx->code ?? '-',
                        $this->formatPaymentMethod($tx->payment_method),
                        (float) $tx->amount_paid,
                        Carbon::parse($tx->payment_date),
                        $tx->reference_number,
                    ];
                }
            }
        }

        return $data;
    }

    public function columnFormats(): array
    {
        return [
            'C' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'I' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'J' => NumberFormat::FORMAT_DATE_XLSX14,
        ];
    }

    public function title(): string
    {
        return 'Transaksi Bulanan';
    }

    public function startCell(): string
    {
        return 'A6';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $this->applyReportHeader($event, $this->title(), 'K');
            },
        ];
    }

    private function getMonthName(int $month): string
    {
        return [
            'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
            'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember',
        ][$month - 1] ?? '';
    }

    private function formatPaymentMethod(?string $method): string
    {
        return match ($method) {
            'cash' => 'Tunai',
            'bank_transfer' => 'Transfer',
            'va' => 'VA',
            'qris' => 'QRIS',
            'other' => 'Lainnya',
            default => $method ?? '-',
        };
    }
}

class CollectionByClassSheet implements FromArray, WithHeadings, WithTitle, WithColumnFormatting, ShouldAutoSize, WithEvents, WithCustomStartCell
{
    use HasReportHeader;

    private array $rows;

    public function __construct(array $rows)
    {
        $this->rows = $rows;
    }

    public function headings(): array
    {
        return [
            'Kelas',
            'Total Tagihan',
            'Total Terkumpul',
            'Tunggakan',
            'Tingkat Koleksi',
        ];
    }

    public function array(): array
    {
        $data = [];

        foreach ($this->rows as $row) {
            $data[] = [
                $row['class_name'],
                (float) $row['total_invoiced'],
                (float) $row['total_paid'],
                (float) $row['outstanding'],
                ($row['collection_rate'] ?? 0) / 100,
            ];
        }

        return $data;
    }

    public function columnFormats(): array
    {
        return [
            'B' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'C' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'D' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'E' => NumberFormat::FORMAT_PERCENTAGE_00,
        ];
    }

    public function title(): string
    {
        return 'Rekap Kelas';
    }

    public function startCell(): string
    {
        return 'A6';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $this->applyReportHeader($event, $this->title(), 'E');
            },
        ];
    }
}

class StudentRecapSheet implements FromArray, WithHeadings, WithTitle, WithColumnFormatting, ShouldAutoSize, WithEvents, WithCustomStartCell
{
    use HasReportHeader;

    private string $granularity;
    private ?int $month;
    private ?int $year;

    public function __construct(string $granularity, ?int $month, ?int $year)
    {
        $this->granularity = $granularity;
        $this->month = $month;
        $this->year = $year;
    }

    public function headings(): array
    {
        return [
            'Nama Siswa',
            'NIS',
            'Kelas',
            'Total Tagihan',
            'Total Pembayaran',
            'Tunggakan',
            'Tingkat Koleksi',
        ];
    }

    public function array(): array
    {
        $invoiceRows = $this->buildInvoiceQuery()
            ->selectRaw('students.id as student_id, students.name as student_name, students.nis, classes.code as class_code, SUM(invoices.total_amount) as total_invoiced')
            ->groupBy('students.id', 'students.name', 'students.nis', 'classes.code')
            ->get();

        $receiptRows = $this->buildReceiptQuery()
            ->selectRaw('students.id as student_id, SUM(receipts.amount_paid) as total_paid')
            ->groupBy('students.id')
            ->get();

        $rowsByStudent = [];

        foreach ($invoiceRows as $row) {
            $rowsByStudent[$row->student_id] = [
                'student_name' => $row->student_name,
                'nis' => $row->nis,
                'class_code' => $row->class_code ?? '-',
                'total_invoiced' => (float) $row->total_invoiced,
                'total_paid' => 0.0,
            ];
        }

        foreach ($receiptRows as $row) {
            if (isset($rowsByStudent[$row->student_id])) {
                $rowsByStudent[$row->student_id]['total_paid'] = (float) $row->total_paid;
            }
        }

        $rows = array_values($rowsByStudent);
        usort($rows, function ($a, $b) {
            $classCompare = strcmp((string) $a['class_code'], (string) $b['class_code']);
            if ($classCompare !== 0) {
                return $classCompare;
            }

            return strcmp((string) $a['student_name'], (string) $b['student_name']);
        });

        $data = [];
        foreach ($rows as $row) {
            $outstanding = max(0, $row['total_invoiced'] - $row['total_paid']);
            $collectionRate = $row['total_invoiced'] > 0
                ? $row['total_paid'] / $row['total_invoiced']
                : 0;

            $data[] = [
                $row['student_name'],
                $row['nis'],
                $row['class_code'],
                (float) $row['total_invoiced'],
                (float) $row['total_paid'],
                (float) $outstanding,
                $collectionRate,
            ];
        }

        return $data;
    }

    public function columnFormats(): array
    {
        return [
            'D' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'E' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'F' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'G' => NumberFormat::FORMAT_PERCENTAGE_00,
        ];
    }

    public function title(): string
    {
        return 'Rekap Siswa';
    }

    public function startCell(): string
    {
        return 'A6';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $this->applyReportHeader($event, $this->title(), 'G');
            },
        ];
    }

    private function buildReceiptQuery()
    {
        $query = DB::table('receipts')
            ->join('invoices', 'receipts.invoice_id', '=', 'invoices.id')
            ->join('students', 'invoices.student_id', '=', 'students.id');

        if ($this->granularity === 'monthly') {
            $query->whereYear('receipts.payment_date', $this->year);
            if (!empty($this->month)) {
                $query->whereMonth('receipts.payment_date', $this->month);
            }
        } else {
            if (!empty($this->year)) {
                $query->whereYear('receipts.payment_date', $this->year);
            }
        }

        return $query;
    }

    private function buildInvoiceQuery()
    {
        $query = DB::table('invoices')
            ->join('students', 'invoices.student_id', '=', 'students.id')
            ->leftJoin('student_class_histories as sch', function ($join) {
                $join->on('students.id', '=', 'sch.student_id')
                    ->where('sch.is_active', true);
            })
            ->leftJoin('classes', 'sch.class_id', '=', 'classes.id');

        if ($this->granularity === 'monthly') {
            $query->whereYear('invoices.created_at', $this->year);
            if (!empty($this->month)) {
                $query->whereMonth('invoices.created_at', $this->month);
            }
        } else {
            if (!empty($this->year)) {
                $query->whereYear('invoices.created_at', $this->year);
            }
        }

        return $query;
    }
}

class ComprehensiveDetailSheet implements FromArray, WithHeadings, WithTitle, WithColumnFormatting, ShouldAutoSize, WithEvents, WithCustomStartCell
{
    use HasReportHeader;

    private string $granularity;
    private ?int $month;
    private ?int $year;

    public function __construct(string $granularity, ?int $month, ?int $year)
    {
        $this->granularity = $granularity;
        $this->month = $month;
        $this->year = $year;
    }

    public function headings(): array
    {
        return [
            'Nama Siswa',
            'NIS',
            'Kelas',
            'No Invoice',
            'Tgl Invoice',
            'Tgl Jatuh Tempo',
            'Jenis Pemasukan',
            'Jenis Tagihan',
            'Deskripsi Item',
            'Nominal Item',
            'Sub Total',
            'Diskon',
            'Total Tagihan',
            'No Kwitansi',
            'Metode Bayar',
            'Jumlah Bayar',
            'Tgl Bayar',
        ];
    }

    public function array(): array
    {
        $rows = $this->buildQuery()
            ->select([
                'students.name as student_name',
                'students.nis',
                'classes.code as class_code',
                'invoices.invoice_number',
                'invoices.issued_at',
                'invoices.due_date',
                'invoices.sub_total',
                'invoices.discount_amount',
                'invoices.total_amount',
                'income_types.name as income_type',
                'tariffs.billing_type',
                'invoice_items.description',
                'invoice_items.final_amount',
                'receipts.receipt_number',
                'receipts.payment_method',
                'receipts.amount_paid',
                'receipts.payment_date',
            ])
            ->orderBy('students.name')
            ->orderBy('invoices.invoice_number')
            ->orderBy('invoice_items.created_at')
            ->get();

        $data = [];
        foreach ($rows as $row) {
            $data[] = [
                $row->student_name,
                $row->nis,
                $row->class_code ?? '-',
                $row->invoice_number,
                $row->issued_at ? Carbon::parse($row->issued_at) : null,
                $row->due_date ? Carbon::parse($row->due_date) : null,
                $row->income_type ?? '-',
                $this->formatBillingType($row->billing_type),
                $row->description,
                (float) ($row->final_amount ?? 0),
                (float) ($row->sub_total ?? 0),
                (float) ($row->discount_amount ?? 0),
                (float) ($row->total_amount ?? 0),
                $row->receipt_number ?? '-',
                $this->formatPaymentMethod($row->payment_method),
                (float) ($row->amount_paid ?? 0),
                $row->payment_date ? Carbon::parse($row->payment_date) : null,
            ];
        }

        return $data;
    }

    public function columnFormats(): array
    {
        return [
            'E' => NumberFormat::FORMAT_DATE_YYYYMMDD2,
            'F' => NumberFormat::FORMAT_DATE_YYYYMMDD2,
            'J' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'K' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'L' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'M' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'P' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'Q' => NumberFormat::FORMAT_DATE_YYYYMMDD2,
        ];
    }

    public function title(): string
    {
        return 'Detail Lengkap';
    }

    public function startCell(): string
    {
        return 'A6';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $this->applyReportHeader($event, $this->title(), 'Q');
            },
        ];
    }

    private function buildQuery()
    {
        $query = DB::table('invoice_items')
            ->join('invoices', 'invoice_items.invoice_id', '=', 'invoices.id')
            ->join('students', 'invoices.student_id', '=', 'students.id')
            ->leftJoin('student_class_histories as sch', function ($join) {
                $join->on('students.id', '=', 'sch.student_id')
                    ->where('sch.is_active', true);
            })
            ->leftJoin('classes', 'sch.class_id', '=', 'classes.id')
            ->leftJoin('tariffs', 'invoice_items.tariff_id', '=', 'tariffs.id')
            ->leftJoin('income_types', 'tariffs.income_type_id', '=', 'income_types.id')
            ->leftJoin('receipts', 'invoices.id', '=', 'receipts.invoice_id');

        if ($this->granularity === 'monthly') {
            $query->whereYear('invoices.created_at', $this->year);
            if (!empty($this->month)) {
                $query->whereMonth('invoices.created_at', $this->month);
            }
        } else {
            if (!empty($this->year)) {
                $query->whereYear('invoices.created_at', $this->year);
            }
        }

        return $query;
    }

    private function formatPaymentMethod(?string $method): string
    {
        return match ($method) {
            'cash' => 'Tunai',
            'bank_transfer' => 'Transfer Bank',
            'va' => 'Virtual Account',
            'qris' => 'QRIS',
            'other' => 'Lainnya',
            default => $method ?? '-',
        };
    }

    private function formatBillingType(?string $type): string
    {
        return match ($type) {
            'once' => 'Sekali Bayar',
            'monthly' => 'Bulanan',
            'yearly' => 'Tahunan',
            'daily' => 'Harian',
            'penalty' => 'Denda',
            default => $type ?? '-',
        };
    }
}

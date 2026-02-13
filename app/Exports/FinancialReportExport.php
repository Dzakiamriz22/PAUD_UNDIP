<?php

namespace App\Exports;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class FinancialReportExport implements WithMultipleSheets
{
    private string $granularity;
    private ?int $month;
    private ?int $year;
    private array $summary;
    private array $reportRows;
    private array $incomeSources;
    private array $collectionByClass;

    public function __construct(
        string $granularity,
        ?int $month,
        ?int $year,
        array $summary,
        array $reportRows,
        array $incomeSources,
        array $collectionByClass
    ) {
        $this->granularity = $granularity;
        $this->month = $month;
        $this->year = $year;
        $this->summary = $summary;
        $this->reportRows = $reportRows;
        $this->incomeSources = $incomeSources;
        $this->collectionByClass = $collectionByClass;
    }

    public function sheets(): array
    {
        return [
            new SummarySheet($this->summary),
            new AggregateSheet($this->reportRows),
            new IncomeSourcesSheet($this->incomeSources),
            new CollectionByClassSheet($this->collectionByClass),
            new StudentRecapSheet($this->granularity, $this->month, $this->year),
            new ItemDetailSheet($this->granularity, $this->month, $this->year),
            new ComprehensiveDetailSheet($this->granularity, $this->month, $this->year),
            new PaymentDetailSheet($this->granularity, $this->month, $this->year),
        ];
    }
}

class SummarySheet implements FromArray, WithHeadings, WithTitle, ShouldAutoSize
{
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
}

class AggregateSheet implements FromArray, WithHeadings, WithTitle, WithColumnFormatting, ShouldAutoSize
{
    private array $rows;

    public function __construct(array $rows)
    {
        $this->rows = $rows;
    }

    public function headings(): array
    {
        return ['Periode', 'Jumlah Transaksi', 'Total Pembayaran'];
    }

    public function array(): array
    {
        $data = [];
        foreach ($this->rows as $row) {
            $period = $row['month']
                ? Carbon::createFromDate($row['year'], $row['month'], 1)->format('F Y')
                : (string) $row['year'];

            $data[] = [
                $period,
                (int) $row['count'],
                (float) $row['total_amount'],
            ];
        }

        return $data;
    }

    public function columnFormats(): array
    {
        return [
            'C' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
        ];
    }

    public function title(): string
    {
        return 'Laporan Agregat';
    }
}

class IncomeSourcesSheet implements FromArray, WithHeadings, WithTitle, WithColumnFormatting, ShouldAutoSize
{
    private array $sources;

    public function __construct(array $sources)
    {
        $this->sources = $sources;
    }

    public function headings(): array
    {
        return ['Sumber', 'Jumlah Item', 'Total', 'Persentase'];
    }

    public function array(): array
    {
        $data = [];
        $grand = array_sum(array_column($this->sources, 'total_amount') ?: [0]);

        foreach ($this->sources as $source) {
            $pct = $grand > 0 ? ($source['total_amount'] / $grand) : 0;
            $data[] = [
                $source['income_type'],
                (int) $source['items_count'],
                (float) $source['total_amount'],
                $pct,
            ];
        }

        return $data;
    }

    public function columnFormats(): array
    {
        return [
            'C' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'D' => NumberFormat::FORMAT_PERCENTAGE_00,
        ];
    }

    public function title(): string
    {
        return 'Sumber Pemasukan';
    }
}

class CollectionByClassSheet implements FromArray, WithHeadings, WithTitle, WithColumnFormatting, ShouldAutoSize
{
    private array $rows;

    public function __construct(array $rows)
    {
        $this->rows = $rows;
    }

    public function headings(): array
    {
        return ['Kelas', 'Jumlah Siswa', 'Total Tagihan', 'Pembayaran', 'Tunggakan', 'Tingkat Koleksi'];
    }

    public function array(): array
    {
        $data = [];
        foreach ($this->rows as $row) {
            $data[] = [
                $row['class_name'],
                (int) $row['student_count'],
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
            'C' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'D' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'E' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'F' => NumberFormat::FORMAT_PERCENTAGE_00,
        ];
    }

    public function title(): string
    {
        return 'Rekap Kelas';
    }
}

class StudentRecapSheet implements FromArray, WithHeadings, WithTitle, WithColumnFormatting, ShouldAutoSize
{
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
            'Jumlah Invoice',
            'Jumlah Pembayaran',
            'Pembayaran Terakhir',
        ];
    }

    public function array(): array
    {
        $invoiceRows = $this->buildInvoiceQuery()
            ->selectRaw('students.id as student_id, students.name as student_name, students.nis, classes.code as class_code, SUM(invoices.total_amount) as total_invoiced, COUNT(invoices.id) as invoice_count')
            ->groupBy('students.id', 'students.name', 'students.nis', 'classes.code')
            ->get();

        $receiptRows = $this->buildReceiptQuery()
            ->selectRaw('students.id as student_id, students.name as student_name, students.nis, classes.code as class_code, SUM(receipts.amount_paid) as total_paid, COUNT(receipts.id) as payment_count, MAX(receipts.payment_date) as last_payment_date')
            ->groupBy('students.id', 'students.name', 'students.nis', 'classes.code')
            ->get();

        $rowsByStudent = [];

        foreach ($invoiceRows as $row) {
            $rowsByStudent[$row->student_id] = [
                'student_name' => $row->student_name,
                'nis' => $row->nis,
                'class_code' => $row->class_code ?? '-',
                'total_invoiced' => (float) $row->total_invoiced,
                'invoice_count' => (int) $row->invoice_count,
                'total_paid' => 0.0,
                'payment_count' => 0,
                'last_payment_date' => null,
            ];
        }

        foreach ($receiptRows as $row) {
            if (!isset($rowsByStudent[$row->student_id])) {
                $rowsByStudent[$row->student_id] = [
                    'student_name' => $row->student_name,
                    'nis' => $row->nis,
                    'class_code' => $row->class_code ?? '-',
                    'total_invoiced' => 0.0,
                    'invoice_count' => 0,
                    'total_paid' => 0.0,
                    'payment_count' => 0,
                    'last_payment_date' => null,
                ];
            }

            $rowsByStudent[$row->student_id]['total_paid'] = (float) $row->total_paid;
            $rowsByStudent[$row->student_id]['payment_count'] = (int) $row->payment_count;
            $rowsByStudent[$row->student_id]['last_payment_date'] = $row->last_payment_date
                ? Carbon::parse($row->last_payment_date)
                : null;
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
                (int) $row['invoice_count'],
                (int) $row['payment_count'],
                $row['last_payment_date'],
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
            'J' => NumberFormat::FORMAT_DATE_YYYYMMDD2,
        ];
    }

    public function title(): string
    {
        return 'Rekap Siswa';
    }

    private function buildReceiptQuery()
    {
        $query = DB::table('receipts')
            ->join('invoices', 'receipts.invoice_id', '=', 'invoices.id')
            ->join('students', 'invoices.student_id', '=', 'students.id')
            ->leftJoin('student_class_histories as sch', function ($join) {
                $join->on('students.id', '=', 'sch.student_id')
                    ->where('sch.is_active', true);
            })
            ->leftJoin('classes', 'sch.class_id', '=', 'classes.id');

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

class PaymentDetailSheet implements FromArray, WithHeadings, WithTitle, WithColumnFormatting, ShouldAutoSize
{
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
            'Tanggal Bayar',
            'Nomor Kwitansi',
            'Nomor Invoice',
            'Nama Siswa',
            'NIS',
            'Kelas',
            'Metode Pembayaran',
            'Jumlah Bayar',
            'Nomor Referensi',
            'Catatan',
        ];
    }

    public function array(): array
    {
        $rows = $this->buildReceiptQuery()
            ->select([
                'receipts.payment_date',
                'receipts.receipt_number',
                'invoices.invoice_number',
                'students.name as student_name',
                'students.nis',
                'classes.code as class_code',
                'receipts.payment_method',
                'receipts.amount_paid',
                'receipts.reference_number',
                'receipts.note',
            ])
            ->orderBy('receipts.payment_date')
            ->get();

        $data = [];
        foreach ($rows as $row) {
            $data[] = [
                $row->payment_date ? Carbon::parse($row->payment_date) : null,
                $row->receipt_number,
                $row->invoice_number,
                $row->student_name,
                $row->nis,
                $row->class_code ?? '-',
                $this->formatPaymentMethod($row->payment_method),
                (float) $row->amount_paid,
                $row->reference_number,
                $row->note,
            ];
        }

        return $data;
    }

    public function columnFormats(): array
    {
        return [
            'A' => NumberFormat::FORMAT_DATE_YYYYMMDD2,
            'H' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
        ];
    }

    public function title(): string
    {
        return 'Detail Pembayaran';
    }

    private function buildReceiptQuery()
    {
        $query = DB::table('receipts')
            ->join('invoices', 'receipts.invoice_id', '=', 'invoices.id')
            ->join('students', 'invoices.student_id', '=', 'students.id')
            ->leftJoin('student_class_histories as sch', function ($join) {
                $join->on('students.id', '=', 'sch.student_id')
                    ->where('sch.is_active', true);
            })
            ->leftJoin('classes', 'sch.class_id', '=', 'classes.id');

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
}

class ItemDetailSheet implements FromArray, WithHeadings, WithTitle, WithColumnFormatting, ShouldAutoSize
{
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
            'Nomor Invoice',
            'Nama Siswa',
            'NIS',
            'Kelas',
            'Jenis Pemasukan',
            'Jenis Tagihan',
            'Periode Bulan',
            'Periode Tahun',
            'Tanggal Harian',
            'Deskripsi Item',
            'Nominal',
        ];
    }

    public function array(): array
    {
        $rows = $this->buildInvoiceItemQuery()
            ->select([
                'invoices.invoice_number',
                'students.name as student_name',
                'students.nis',
                'classes.code as class_code',
                'income_types.name as income_type',
                'tariffs.billing_type',
                'invoice_items.period_month',
                'invoice_items.period_year',
                'invoice_items.period_day',
                'invoice_items.description',
                'invoice_items.final_amount',
            ])
            ->orderBy('students.name')
            ->orderBy('invoices.invoice_number')
            ->get();

        $data = [];
        foreach ($rows as $row) {
            $data[] = [
                $row->invoice_number,
                $row->student_name,
                $row->nis,
                $row->class_code ?? '-',
                $row->income_type,
                $this->formatBillingType($row->billing_type),
                $row->period_month,
                $row->period_year,
                $row->period_day ? Carbon::parse($row->period_day) : null,
                $row->description,
                (float) $row->final_amount,
            ];
        }

        return $data;
    }

    public function columnFormats(): array
    {
        return [
            'I' => NumberFormat::FORMAT_DATE_YYYYMMDD2,
            'K' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
        ];
    }

    public function title(): string
    {
        return 'Detail Item';
    }

    private function buildInvoiceItemQuery()
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
            ->leftJoin('income_types', 'tariffs.income_type_id', '=', 'income_types.id');

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

class ComprehensiveDetailSheet implements FromArray, WithHeadings, WithTitle, WithColumnFormatting, ShouldAutoSize
{
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
            'Status Invoice',
            'Nomor Invoice',
            'Tanggal Invoice',
            'Jatuh Tempo',
            'Tanggal Bayar',
            'Nomor Kwitansi',
            'Metode Pembayaran',
            'Nomor Referensi',
            'Virtual Account',
            'Jenis Pemasukan',
            'Jenis Tagihan',
            'Periode Bulan',
            'Periode Tahun',
            'Tanggal Harian',
            'Deskripsi Item',
            'Nominal Item',
            'Sub Total Invoice',
            'Total Diskon',
            'Total Tagihan',
            'Total Pembayaran',
            'Tunggakan',
        ];
    }

    public function array(): array
    {
        $rows = $this->buildQuery()
            ->select([
                'students.name as student_name',
                'students.nis',
                'classes.code as class_code',
                'invoices.status',
                'invoices.invoice_number',
                'invoices.issued_at',
                'invoices.due_date',
                'receipts.payment_date',
                'receipts.receipt_number',
                'receipts.payment_method',
                'receipts.reference_number',
                'invoices.va_bank',
                'invoices.va_number',
                'income_types.name as income_type',
                'tariffs.billing_type',
                'invoice_items.period_month',
                'invoice_items.period_year',
                'invoice_items.period_day',
                'invoice_items.description',
                'invoice_items.final_amount',
                'invoices.sub_total',
                'invoices.discount_amount',
                'invoices.total_amount',
                'receipts.amount_paid',
            ])
            ->orderBy('students.name')
            ->orderBy('invoices.invoice_number')
            ->orderBy('invoice_items.created_at')
            ->get();

        $data = [];
        foreach ($rows as $row) {
            $totalPaid = (float) ($row->amount_paid ?? 0);
            $totalInvoiced = (float) ($row->total_amount ?? 0);
            $outstanding = max(0, $totalInvoiced - $totalPaid);

            $data[] = [
                $row->student_name,
                $row->nis,
                $row->class_code ?? '-',
                $row->status ?? '-',
                $row->invoice_number,
                $row->issued_at ? Carbon::parse($row->issued_at) : null,
                $row->due_date ? Carbon::parse($row->due_date) : null,
                $row->payment_date ? Carbon::parse($row->payment_date) : null,
                $row->receipt_number,
                $this->formatPaymentMethod($row->payment_method),
                $row->reference_number,
                $this->formatVirtualAccount($row->va_bank, $row->va_number),
                $row->income_type,
                $this->formatBillingType($row->billing_type),
                $row->period_month,
                $row->period_year,
                $row->period_day ? Carbon::parse($row->period_day) : null,
                $row->description,
                (float) $row->final_amount,
                (float) ($row->sub_total ?? 0),
                (float) ($row->discount_amount ?? 0),
                $totalInvoiced,
                $totalPaid,
                $outstanding,
            ];
        }

        return $data;
    }

    public function columnFormats(): array
    {
        return [
            'F' => NumberFormat::FORMAT_DATE_YYYYMMDD2,
            'G' => NumberFormat::FORMAT_DATE_YYYYMMDD2,
            'H' => NumberFormat::FORMAT_DATE_YYYYMMDD2,
            'P' => NumberFormat::FORMAT_DATE_YYYYMMDD2,
            'R' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'S' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'T' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'U' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'V' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'W' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
        ];
    }

    public function title(): string
    {
        return 'Detail Lengkap';
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

    private function formatVirtualAccount(?string $bank, ?string $number): string
    {
        $bank = $bank ?: 'BNI';
        $number = $number ?: '1234567890';

        return trim($bank . ' - ' . $number);
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

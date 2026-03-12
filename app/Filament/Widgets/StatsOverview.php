<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class StatsOverview extends BaseWidget
{
    protected ?string $heading = 'Ringkasan Keuangan';

    protected function getStats(): array
    {
        $now = now();
        // helper to format rupiah
        $format = fn($v) => 'Rp ' . number_format($v, 0, ',', '.');

        // helper to calculate percent change
        $pct = function (float $current, float $previous): string {
            if ($previous == 0) {
                return $current === 0 ? '0%' : '—';
            }
            $change = ($current - $previous) / $previous * 100;
            return ($change > 0 ? '+' : '') . round($change, 1) . '%';
        };

        // Pemasukan (invoiced)
        $pemasukan = 0;
        $pemasukanPrev = 0;
        $pemasukanYtd = 0;
        if (Schema::hasTable('invoices')) {
            $pemasukan = (float) DB::table('invoices')
                ->whereYear('issued_at', $now->year)
                ->whereMonth('issued_at', $now->month)
                ->sum('total_amount');

            $prev = (clone $now)->subMonth();
            $pemasukanPrev = (float) DB::table('invoices')
                ->whereYear('issued_at', $prev->year)
                ->whereMonth('issued_at', $prev->month)
                ->sum('total_amount');

            $pemasukanYtd = (float) DB::table('invoices')
                ->whereYear('issued_at', $now->year)
                ->sum('total_amount');
        }

        // Penerimaan (actual payments)
        $penerimaan = 0;
        $penerimaanPrev = 0;
        $penerimaanYtd = 0;
        if (Schema::hasTable('receipts')) {
            $penerimaan = (float) DB::table('receipts')
                ->whereYear('payment_date', $now->year)
                ->whereMonth('payment_date', $now->month)
                ->sum('amount_paid');

            $prev = (clone $now)->subMonth();
            $penerimaanPrev = (float) DB::table('receipts')
                ->whereYear('payment_date', $prev->year)
                ->whereMonth('payment_date', $prev->month)
                ->sum('amount_paid');

            $penerimaanYtd = (float) DB::table('receipts')
                ->whereYear('payment_date', $now->year)
                ->sum('amount_paid');
        }

        // Outstanding / unpaid invoices
        $outstandingCount = 0;
        $outstandingTotal = 0.0;
        if (Schema::hasTable('invoices')) {
            $outstandingCount = (int) DB::table('invoices')
                ->where('status', 'unpaid')
                ->count();

            $outstandingTotal = (float) DB::table('invoices')
                ->where('status', 'unpaid')
                ->sum('total_amount');
        }

        // Average payment per receipt (this month)
        $avgPayment = 0.0;
        if (Schema::hasTable('receipts')) {
            $countReceipts = (int) DB::table('receipts')
                ->whereYear('payment_date', $now->year)
                ->whereMonth('payment_date', $now->month)
                ->count();

            $avgPayment = $countReceipts > 0 ? $penerimaan / $countReceipts : 0.0;
        }

        // active students
        $activeStudents = 0;
        if (Schema::hasTable('students') && Schema::hasTable('student_class_histories')) {
            $activeStudents = DB::table('students')
                ->join('student_class_histories', 'students.id', '=', 'student_class_histories.student_id')
                ->where('student_class_histories.is_active', true)
                ->distinct('students.id')
                ->count('students.id');
        }

        return [
            Stat::make('Pemasukan (Bulan ini)', $format($pemasukan))
                ->description('Total tagihan yang diterbitkan — MoM: ' . $pct($pemasukan, $pemasukanPrev) . ' · YTD: ' . $format($pemasukanYtd))
                ->color('primary')
                ->chart([]),

            Stat::make('Penerimaan (Bulan ini)', $format($penerimaan))
                ->description('Total pembayaran diterima — MoM: ' . $pct($penerimaan, $penerimaanPrev) . ' · YTD: ' . $format($penerimaanYtd))
                ->color('success')
                ->chart([]),

            Stat::make('Piutang (Tagihan belum lunas)', number_format($outstandingCount))
                ->description('Jumlah tagihan belum lunas · Total: ' . $format($outstandingTotal))
                ->color('danger')
                ->chart([]),

            Stat::make('Siswa Aktif', number_format($activeStudents))
                ->description('Jumlah siswa dengan kelas aktif')
                ->color('secondary')
                ->chart([]),
        ];
    }
}

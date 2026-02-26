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

        // Penerimaan (actual payments) for current month
        $penerimaan = 0;
        if (Schema::hasTable('receipts')) {
            $penerimaan = (float) DB::table('receipts')
                ->whereYear('payment_date', $now->year)
                ->whereMonth('payment_date', $now->month)
                ->sum('amount_paid');
        }

        // Pemasukan (invoiced) for current month
        $pemasukan = 0;
        if (Schema::hasTable('invoices')) {
            $pemasukan = (float) DB::table('invoices')
                ->whereYear('issued_at', $now->year)
                ->whereMonth('issued_at', $now->month)
                ->sum('total_amount');
        }

        $format = fn($v) => 'Rp ' . number_format($v, 0, ',', '.');

        return [
            Stat::make('Penerimaan (Bulan ini)', $format($penerimaan))
                ->description('Total pembayaran diterima (kwitansi)')
                ->color('success')
                ->chart([]),

            Stat::make('Pemasukan (Bulan ini)', $format($pemasukan))
                ->description('Total tagihan / pemasukan yang dihasilkan')
                ->color('primary')
                ->chart([]),
        ];
    }
}

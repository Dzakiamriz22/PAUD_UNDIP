<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget as BaseWidget;
use Filament\Support\RawJs;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MonthlyIncomeChart extends BaseWidget
{
    protected static ?string $heading = 'Penerimaan vs Pemasukan (12 bulan)';
    protected static ?string $maxHeight = '520px';

    protected function getData(): array
    {
        $now = now();
        $labels = [];
        $receiptsData = [];
        $invoicesData = [];

        for ($i = 11; $i >= 0; $i--) {
            $dt = $now->copy()->subMonths($i);
            $labels[] = $dt->format('M Y');

            $start = $dt->copy()->startOfMonth()->toDateString();
            $end = $dt->copy()->endOfMonth()->toDateString();

            $receiptsSum = Schema::hasTable('receipts')
                ? (float) DB::table('receipts')->whereBetween('payment_date', [$start, $end])->sum('amount_paid')
                : 0.0;

            $invoicesSum = Schema::hasTable('invoices')
                ? (float) DB::table('invoices')->whereBetween('issued_at', [$start, $end])->sum('total_amount')
                : 0.0;

            $receiptsData[] = round($receiptsSum, 2);
            $invoicesData[] = round($invoicesSum, 2);
        }

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Penerimaan (kwitansi)',
                    'data' => $receiptsData,
                    'backgroundColor' => 'rgba(16,185,129,0.08)',
                    'borderColor' => '#10B981',
                    'borderWidth' => 3,
                    'tension' => 0.3,
                    'pointRadius' => 3,
                ],
                [
                    'label' => 'Pemasukan (tagihan)',
                    'data' => $invoicesData,
                    'backgroundColor' => 'rgba(59,130,246,0.08)',
                    'borderColor' => '#3B82F6',
                    'borderWidth' => 3,
                    'tension' => 0.18,
                    'pointRadius' => 3,
                ],
            ],
        ];
    }

    public function getDescription(): ?string
    {
        $now = now();
        $format = fn($v) => 'Rp ' . number_format($v, 0, ',', '.');

        $currentReceipts = Schema::hasTable('receipts')
            ? (float) DB::table('receipts')->whereYear('payment_date', $now->year)->whereMonth('payment_date', $now->month)->sum('amount_paid')
            : 0;

        $currentInvoices = Schema::hasTable('invoices')
            ? (float) DB::table('invoices')->whereYear('issued_at', $now->year)->whereMonth('issued_at', $now->month)->sum('total_amount')
            : 0;

        $ytdReceipts = Schema::hasTable('receipts')
            ? (float) DB::table('receipts')->whereYear('payment_date', $now->year)->sum('amount_paid')
            : 0;

        return 'Penerimaan bulan ini: ' . $format($currentReceipts) . ' · Pemasukan bulan ini: ' . $format($currentInvoices) . ' · YTD Penerimaan: ' . $format($ytdReceipts);
    }

    protected function getOptions(): array | RawJs
    {
        return RawJs::make(<<<'JS'
        {
            responsive: true,
            plugins: {
                legend: { position: 'top' },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const v = context.raw ?? context.parsed.y ?? context.parsed;
                            return context.dataset.label + ': ' + new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(v);
                        }
                    }
                }
            },
            scales: {
                y: {
                    ticks: {
                        callback: function(value) {
                            return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(value);
                        }
                    }
                }
            }
        }
        JS);
    }

    protected function getType(): string
    {
        return 'line';
    }
}

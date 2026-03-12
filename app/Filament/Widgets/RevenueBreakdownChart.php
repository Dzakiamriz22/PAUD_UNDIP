<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget as BaseWidget;
use Filament\Support\RawJs;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RevenueBreakdownChart extends BaseWidget
{
    protected static ?string $heading = 'Distribusi Penerimaan per Jenis';
    protected static ?string $maxHeight = '320px';
    protected static ?string $pollingInterval = null;

    protected function getData(): array
    {
        // prefer receipts, fallback to invoices
        if (Schema::hasTable('receipts') && Schema::hasTable('invoices')) {
            // receipts -> invoices -> invoice_items -> tariffs -> income_types
            $rows = DB::table('receipts')
                ->join('invoices', 'receipts.invoice_id', '=', 'invoices.id')
                ->join('invoice_items', 'invoice_items.invoice_id', '=', 'invoices.id')
                ->leftJoin('tariffs', 'invoice_items.tariff_id', '=', 'tariffs.id')
                ->leftJoin('income_types', 'tariffs.income_type_id', '=', 'income_types.id')
                ->select(DB::raw("COALESCE(income_types.name, 'Lainnya') as label"), DB::raw('SUM(receipts.amount_paid) as total'))
                ->groupBy('label')
                ->get();
        } elseif (Schema::hasTable('invoices')) {
            $rows = DB::table('invoices')
                ->leftJoin('income_types', 'invoices.income_type_id', '=', 'income_types.id')
                ->select(DB::raw("COALESCE(income_types.name, 'Lainnya') as label"), DB::raw('SUM(invoices.total_amount) as total'))
                ->groupBy('label')
                ->get();
        } else {
            return [
                'labels' => [],
                'datasets' => [],
            ];
        }

        $labels = [];
        $data = [];
        foreach ($rows as $row) {
            $labels[] = $row->label ?? 'Lainnya';
            $data[] = (float) $row->total;
        }

        // pastel professional palette
        $palette = [
            '#60A5FA', // blue-400
            '#34D399', // emerald-400
            '#FBBF24', // amber-400
            '#FCA5A5', // red-300
            '#A78BFA', // violet-400
            '#67E8F9', // cyan-300
            '#FDB6E3', // pink-ish
            '#C7C7C7', // gray
            '#FBCB8A',
            '#93C5FD',
        ];

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Distribusi',
                    'data' => $data,
                    'backgroundColor' => array_slice($palette, 0, max(1, count($data))),
                    'borderWidth' => 0,
                ],
            ],
        ];
    }
    
    public function getDescription(): ?string
    {
        // compute total receipts/invoices for label
        if (Schema::hasTable('receipts') && Schema::hasTable('invoices')) {
            $total = (float) DB::table('receipts')->sum('amount_paid');
        } elseif (Schema::hasTable('invoices')) {
            $total = (float) DB::table('invoices')->sum('total_amount');
        } else {
            return null;
        }

        // Return null so Filament doesn't show this in the header — we'll render it below the chart instead
        return null;
    }

    protected function getOptions(): array | \Filament\Support\RawJs
    {
        // compute total for percentage calculations and inject into JS
        if (Schema::hasTable('receipts') && Schema::hasTable('invoices')) {
            $total = (float) DB::table('receipts')->sum('amount_paid');
        } elseif (Schema::hasTable('invoices')) {
            $total = (float) DB::table('invoices')->sum('total_amount');
        } else {
            $total = 0;
        }

        $totalJs = (int) round($total);

        // Draw total in the center of the doughnut and keep legend minimal below
        return RawJs::make(<<<JS
        {
            cutout: '65%',
            responsive: true,
            maintainAspectRatio: false,
            layout: { padding: 8 },
            plugins: {
                legend: { position: 'bottom', align: 'center', labels: { boxWidth: 10, padding: 8, usePointStyle: true, color: '#6B7280', font: { size: 12 } } },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            var label = context.label || '';
                            var value = context.raw ?? context.parsed ?? 0;
                            var pct = 0;
                            if ({$totalJs} > 0) {
                                pct = (value / {$totalJs}) * 100;
                            }
                            var formatted = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(value);
                            return label + ': ' + formatted + ' (' + pct.toFixed(1) + '%)';
                        }
                    }
                },
                centerText: {
                    id: 'centerText',
                    afterDraw: function(chart) {
                        var ctx = chart.ctx;
                        var width = chart.width;
                        var height = chart.height;
                        var centerX = (chart.chartArea.left + chart.chartArea.right) / 2;
                        var centerY = (chart.chartArea.top + chart.chartArea.bottom) / 2;

                        ctx.save();
                        ctx.fillStyle = '#111827';
                        ctx.textAlign = 'center';

                        // label
                        ctx.font = '600 12px Inter, ui-sans-serif, system-ui';
                        ctx.fillStyle = '#6B7280';
                        ctx.fillText('Total', centerX, centerY - 6);

                        // amount
                        ctx.font = '700 16px Inter, ui-sans-serif, system-ui';
                        ctx.fillStyle = '#111827';
                        var formatted = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format({$totalJs});
                        ctx.fillText(formatted, centerX, centerY + 18);

                        ctx.restore();
                    }
                }
            },
            elements: {
                arc: { borderWidth: 0 }
            }
        }
        JS
        );
    }
    
    protected function getType(): string
    {
        return 'doughnut';
    }
}

<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget as BaseWidget;
use Filament\Support\RawJs;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RevenueByProgramChart extends BaseWidget
{
    protected static ?string $heading = 'Pendapatan per Program / Kelas';
    protected static ?string $maxHeight = '360px';

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        if (! Schema::hasTable('receipts')) {
            return ['labels' => [], 'datasets' => []];
        }

        // Prefer invoiced amounts (invoice_items.final_amount) to show issued revenue (pemasukan)
        if (Schema::hasTable('invoice_items')) {
            $rows = DB::table('invoice_items')
                ->join('invoices', 'invoice_items.invoice_id', '=', 'invoices.id')
                ->leftJoin('tariffs', 'invoice_items.tariff_id', '=', 'tariffs.id')
                ->leftJoin('income_types', 'tariffs.income_type_id', '=', 'income_types.id')
                ->select(DB::raw("COALESCE(tariffs.class_category, income_types.name, 'Lainnya') as label"), DB::raw('SUM(invoice_items.final_amount) as total'))
                ->groupBy('label')
                ->orderByDesc('total')
                ->limit(10)
                ->get();
        } else {
            // fallback to receipts total
            $rows = DB::table('receipts')
                ->join('invoices', 'receipts.invoice_id', '=', 'invoices.id')
                ->join('invoice_items', 'invoice_items.invoice_id', '=', 'invoices.id')
                ->leftJoin('tariffs', 'invoice_items.tariff_id', '=', 'tariffs.id')
                ->leftJoin('income_types', 'tariffs.income_type_id', '=', 'income_types.id')
                ->select(DB::raw("COALESCE(tariffs.class_category, income_types.name, 'Lainnya') as label"), DB::raw('SUM(receipts.amount_paid) as total'))
                ->groupBy('label')
                ->orderByDesc('total')
                ->limit(10)
                ->get();
        }

        $labels = [];
        $data = [];
        foreach ($rows as $row) {
            $labels[] = $row->label;
            $data[] = (float) $row->total;
        }

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Pendapatan (Rp)',
                    'data' => $data,
                    'backgroundColor' => '#2563EB',
                ],
            ],
        ];
    }

    public function getDescription(): ?string
    {
        if (! Schema::hasTable('receipts')) {
            return null;
        }

        if (Schema::hasTable('invoice_items')) {
            $total = (float) DB::table('invoice_items')->sum('final_amount');
            $label = 'Total Pemasukan';
        } elseif (Schema::hasTable('receipts')) {
            $total = (float) DB::table('receipts')->sum('amount_paid');
            $label = 'Total Penerimaan';
        } else {
            return null;
        }

        return $label . ': Rp ' . number_format($total, 0, ',', '.');
    }

    protected function getOptions(): array | RawJs
    {
        return RawJs::make(<<<'JS'
        {
            indexAxis: 'y',
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            var value = context.raw ?? context.parsed ?? 0;
                            return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(value);
                        }
                    }
                }
            },
            scales: {
                x: {
                    ticks: {
                        callback: function(value) { return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(value); }
                    }
                }
            }
        }
        JS
        );
    }
}

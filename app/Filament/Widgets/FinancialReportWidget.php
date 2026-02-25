<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use App\Models\Receipt;
use App\Models\Invoice;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class FinancialReportWidget extends Widget
{
    protected static bool $isDiscovered = false;
    protected static ?int $sort = -1;
    protected static bool $isLazy = false;
    protected static string $view = 'filament.widgets.financial-report-widget';

    public $monthlyTotal = 0;
    public $yearlyTotal = 0;
    public $outstandingTotal = 0;
    public $topPayers = [];
    public $monthlyExpenses = 0;
    public $yearlyExpenses = 0;
    public $incomeSparkline = [];
    public $expenseSparkline = [];

    public function hasExpenseModel(): bool
    {
        return class_exists(\App\Models\Expense::class);
    }

    public function mount(): void
    {
        $now = Carbon::now();

        $this->monthlyTotal = (float) Receipt::whereYear('payment_date', $now->year)
            ->whereMonth('payment_date', $now->month)
            ->sum('amount_paid');

        $this->yearlyTotal = (float) Receipt::whereYear('payment_date', $now->year)
            ->sum('amount_paid');

        $this->outstandingTotal = (float) Invoice::where('status', 'unpaid')->sum('total_amount');

        $this->topPayers = Receipt::join('invoices', 'receipts.invoice_id', '=', 'invoices.id')
            ->join('students', 'invoices.student_id', '=', 'students.id')
            ->selectRaw('students.id, students.name, SUM(receipts.amount_paid) as total_paid')
            ->groupBy('students.id', 'students.name')
            ->orderByDesc('total_paid')
            ->limit(5)
            ->get()
            ->map(fn($r) => [
                'name' => $r->name,
                'total_paid' => (float) $r->total_paid,
            ])->toArray();

        // Expenses (optional) - only if an Expense model exists
        if ($this->hasExpenseModel()) {
            $expenseModel = \App\Models\Expense::class;

            $this->monthlyExpenses = (float) $expenseModel::whereYear('date', $now->year)
                ->whereMonth('date', $now->month)
                ->sum('amount');

            $this->yearlyExpenses = (float) $expenseModel::whereYear('date', $now->year)
                ->sum('amount');
        } else {
            $this->monthlyExpenses = 0;
            $this->yearlyExpenses = 0;
        }

        // sparkline: last 6 months income and expenses
        $this->incomeSparkline = [];
        $this->expenseSparkline = [];
        for ($i = 5; $i >= 0; $i--) {
            $dt = Carbon::now()->subMonths($i);
            $inc = (float) Receipt::whereYear('payment_date', $dt->year)
                ->whereMonth('payment_date', $dt->month)
                ->sum('amount_paid');
            $this->incomeSparkline[] = $inc;

            if ($this->hasExpenseModel()) {
                $expenseModel = \App\Models\Expense::class;
                $exp = (float) $expenseModel::whereYear('date', $dt->year)
                    ->whereMonth('date', $dt->month)
                    ->sum('amount');
            } else {
                $exp = 0;
            }
            $this->expenseSparkline[] = $exp;
        }
    }
}

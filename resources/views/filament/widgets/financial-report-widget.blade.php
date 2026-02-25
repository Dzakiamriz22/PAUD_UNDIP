<div class="p-4 bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
    <div class="flex items-center justify-between mb-3">
        <div>
            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300">Laporan Keuangan (Ringkasan)</h3>
            <p class="text-xs text-gray-500 dark:text-gray-400">Ringkasan pemasukan dan piutang</p>
        </div>
        <div class="text-sm text-gray-500">
            <a href="{{ \App\Filament\Resources\FinancialReportResource::getUrl() }}" class="text-primary-600 hover:underline">Lihat detail</a>
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-4">
        <div class="p-3 bg-gray-50 dark:bg-gray-900 rounded">
            <div class="text-xs text-gray-500">Bulan ini</div>
            <div class="text-lg font-bold">Rp {{ number_format($this->monthlyTotal, 0, ',', '.') }}</div>
        </div>
        <div class="p-3 bg-gray-50 dark:bg-gray-900 rounded">
            <div class="text-xs text-gray-500">Tahun ini</div>
            <div class="text-lg font-bold">Rp {{ number_format($this->yearlyTotal, 0, ',', '.') }}</div>
        </div>
        <div class="p-3 bg-gray-50 dark:bg-gray-900 rounded">
            <div class="text-xs text-gray-500">Piutang</div>
            <div class="text-lg font-bold">Rp {{ number_format($this->outstandingTotal, 0, ',', '.') }}</div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-4">
        <div class="flex items-center gap-4">
            @php
                $inc = $this->monthlyTotal;
                $exp = $this->monthlyExpenses ?? 0;
                $total = max($inc + $exp, 0);
                $incPercent = $total > 0 ? ($inc / $total) * 100 : 0;
                $radius = 40;
                $circ = 2 * pi() * $radius;
                $incDash = $circ * ($incPercent / 100);
                $expDash = max(0, $circ - $incDash);

                $incomeSpark = $this->incomeSparkline ?? [];
                $expenseSpark = $this->expenseSparkline ?? [];
                $maxSpark = max(array_merge($incomeSpark ?: [0], $expenseSpark ?: [0]));
            @endphp

            <div class="flex items-center">
                <svg width="120" height="120" viewBox="0 0 120 120">
                    <g transform="translate(60,60)">
                        <circle r="{{ $radius }}" fill="transparent" stroke="#e5e7eb" stroke-width="18"></circle>
                        <circle r="{{ $radius }}" fill="transparent" stroke="#10b981" stroke-width="18" stroke-dasharray="{{ $incDash }} {{ $expDash }}" stroke-linecap="round" transform="rotate(-90)"></circle>
                        <text x="0" y="4" text-anchor="middle" font-size="12" fill="#374151">{{ round($incPercent) }}%</text>
                    </g>
                </svg>
            </div>

            <div>
                <div class="text-xs text-gray-500">Pemasukan vs Pengeluaran (bulan ini)</div>
                <div class="font-bold text-lg">Rp {{ number_format($inc, 0, ',', '.') }} <span class="text-sm text-gray-500">/ Rp {{ number_format($exp, 0, ',', '.') }}</span></div>
            </div>
        </div>

        <div>
            <div class="text-xs text-gray-500 mb-2">Pemasukan - 6 bulan terakhir</div>
            <svg class="w-full h-16" viewBox="0 0 120 40" preserveAspectRatio="none">
                @php
                    $w = 120; $h = 40; $pointsInc = []; $pointsExp = [];
                    $count = max(count($incomeSpark), 1);
                    for ($i = 0; $i < $count; $i++) {
                        $x = ($i / max(1, $count - 1)) * $w;
                        $yInc = $maxSpark > 0 ? $h - (($incomeSpark[$i] ?? 0) / $maxSpark) * ($h - 6) : $h;
                        $yExp = $maxSpark > 0 ? $h - (($expenseSpark[$i] ?? 0) / $maxSpark) * ($h - 6) : $h;
                        $pointsInc[] = "$x,$yInc";
                        $pointsExp[] = "$x,$yExp";
                    }
                @endphp

                @if(count($pointsExp) > 0)
                    <polyline fill="none" stroke="#ef4444" stroke-width="2" points="{{ implode(' ', $pointsExp) }}" opacity="0.6" />
                @endif
                <polyline fill="none" stroke="#3b82f6" stroke-width="2" points="{{ implode(' ', $pointsInc) }}" />
            </svg>
        </div>
    </div>

    <div>
        <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Top Pembayar</h4>
        @if(count($this->topPayers) > 0)
            <ul class="space-y-2">
                @foreach($this->topPayers as $payer)
                    <li class="flex items-center justify-between text-sm">
                        <span class="text-gray-900 dark:text-gray-100">{{ $payer['name'] }}</span>
                        <span class="text-gray-600 dark:text-gray-300">Rp {{ number_format($payer['total_paid'], 0, ',', '.') }}</span>
                    </li>
                @endforeach
            </ul>
        @else
            <p class="text-xs text-gray-500">Belum ada transaksi.</p>
        @endif
    </div>
</div>

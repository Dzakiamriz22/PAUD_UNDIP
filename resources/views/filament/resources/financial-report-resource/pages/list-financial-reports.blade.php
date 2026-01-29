@php
    /**
     * Variables provided by Page class:
     * - $totalInvoiced
     * - $totalPaid
     * - $totalOutstanding
     * - $totalDiscounts
     * - $recentInvoices
     */
@endphp

<div class="filament-page">
    <div class="space-y-6">

        <!-- Top filter toolbar -->
        <div class="bg-white shadow rounded-lg p-4">
            <div class="flex flex-col md:flex-row md:items-end md:space-x-4 gap-3">
                <div class="flex-1">
                    <label class="block text-xs text-gray-600">Periode</label>
                    <div class="flex gap-2 mt-1">
                        <select wire:model="granularity" class="border rounded px-3 py-2 bg-white">
                            <option value="monthly">Bulanan</option>
                            <option value="yearly">Tahunan</option>
                        </select>

                        <select wire:model="month" class="border rounded px-3 py-2 bg-white" @if($granularity === 'yearly') disabled @endif>
                            @foreach(range(1,12) as $m)
                                <option value="{{ $m }}">{{ DateTime::createFromFormat('!m', $m)->format('F') }}</option>
                            @endforeach
                        </select>

                        <input type="number" wire:model="year" class="w-28 border rounded px-3 py-2" />
                    </div>
                </div>

                <div class="flex items-center space-x-2">
                    <button wire:click="applyFilters" class="px-4 py-2 bg-primary-600 text-white rounded">Terapkan</button>
                    <button wire:click="$refresh" class="px-3 py-2 border rounded">Reset</button>
                </div>
            </div>
        </div>

        <!-- Metric cards (expressive) -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="p-4 bg-white rounded-lg shadow flex flex-col justify-between">
                <div>
                    <div class="text-xs text-gray-500">Total Pembayaran (Periode)</div>
                    <div class="mt-2 text-2xl font-semibold">Rp {{ number_format($currentPeriodTotal, 0, ',', '.') }}</div>
                </div>
                <div class="flex items-center justify-between mt-4">
                    <div class="text-sm {{ $periodChangePercent >= 0 ? 'text-green-600' : 'text-red-600' }} font-medium">
                        {!! $periodChangePercent >= 0 ? '&uarr;' : '&darr;' !!} {{ abs($periodChangePercent) }}%
                    </div>
                    <div class="w-28">
                        <!-- sparkline -->
                        @php
                            $vals = $sparkline;
                            $max = max($vals) ?: 1;
                            $points = collect($vals)->map(function($v, $i) use ($max, $vals) {
                                $x = ($i / (count($vals)-1)) * 100;
                                $y = 100 - (($v / $max) * 100);
                                return "$x,$y";
                            })->implode(' ');
                        @endphp
                        <svg class="w-full h-6" viewBox="0 0 100 100" preserveAspectRatio="none">
                            <polyline fill="none" stroke="#3b82f6" stroke-width="2" points="{{ $points }}" />
                        </svg>
                    </div>
                </div>
            </div>

            <div class="p-4 bg-white rounded-lg shadow">
                <div class="text-xs text-gray-500">Total Tagihan</div>
                <div class="mt-2 text-xl font-semibold">Rp {{ number_format($totalInvoiced, 0, ',', '.') }}</div>
            </div>

            <div class="p-4 bg-white rounded-lg shadow">
                <div class="text-xs text-gray-500">Total Outstanding</div>
                <div class="mt-2 text-xl font-semibold text-red-600">Rp {{ number_format($totalOutstanding, 0, ',', '.') }}</div>
            </div>

            <div class="p-4 bg-white rounded-lg shadow">
                <div class="text-xs text-gray-500">Total Diskon</div>
                <div class="mt-2 text-xl font-semibold text-indigo-600">Rp {{ number_format($totalDiscounts, 0, ',', '.') }}</div>
            </div>
        </div>

        <!-- Results table -->
        <div class="bg-white rounded-lg shadow p-4">
            <h3 class="text-lg font-medium">Laporan Agregat</h3>
            <div class="mt-4 overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="text-xs text-gray-500 uppercase">
                        <tr>
                            <th class="px-3 py-2">Periode</th>
                            <th class="px-3 py-2">Jumlah Transaksi</th>
                            <th class="px-3 py-2">Total Pembayaran</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @forelse($reportRows as $r)
                            <tr>
                                <td class="px-3 py-2">
                                    @if($r['month'])
                                        {{ DateTime::createFromFormat('!m', $r['month'])->format('F') }} {{ $r['year'] }}
                                    @else
                                        {{ $r['year'] }}
                                    @endif
                                </td>
                                <td class="px-3 py-2">{{ $r['count'] }}</td>
                                <td class="px-3 py-2">Rp {{ number_format($r['total_amount'], 0, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td class="px-3 py-2" colspan="3">Tidak ada data untuk filter ini.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Income sources breakdown -->
        <div class="bg-white rounded-lg shadow p-4">
            <h3 class="text-lg font-medium">Sumber Pemasukan</h3>
            <p class="text-sm text-gray-500 mt-1">Ringkasan berdasarkan jenis pendapatan (tidak termasuk diskon).</p>

            <div class="mt-4 overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="text-xs text-gray-500 uppercase">
                        <tr>
                            <th class="px-3 py-2">Sumber</th>
                            <th class="px-3 py-2">Jumlah Item</th>
                            <th class="px-3 py-2">Total (Rp)</th>
                            <th class="px-3 py-2">% dari Pembayaran</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @php $grand = array_sum(array_column($incomeSources, 'total_amount') ?: [0]); @endphp
                        @forelse($incomeSources as $s)
                            @php $pct = $grand > 0 ? ($s['total_amount'] / $grand) * 100 : 0; @endphp
                            <tr>
                                <td class="px-3 py-2 w-1/2">
                                    <div class="font-medium">{{ $s['income_type'] }}</div>
                                </td>
                                <td class="px-3 py-2">{{ $s['items_count'] }}</td>
                                <td class="px-3 py-2">Rp {{ number_format($s['total_amount'], 0, ',', '.') }}</td>
                                <td class="px-3 py-2 w-48">
                                    <div class="bg-gray-100 rounded-full h-3 w-full">
                                        <div class="bg-primary-600 h-3 rounded-full" style="width: {{ round($pct,2) }}%"></div>
                                    </div>
                                    <div class="text-xs text-gray-500 mt-1">{{ number_format($pct, 2) }}%</div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td class="px-3 py-2" colspan="4">Tidak ada data sumber pemasukan untuk filter ini.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

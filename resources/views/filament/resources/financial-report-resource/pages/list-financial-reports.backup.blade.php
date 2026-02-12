@php
    /**
     * Financial Report Page - PAUD UNDIP
     * Sistem Pembayaran: Virtual Account BNI
     * 
     * Variables provided by Page class:
     * - $totalInvoiced: Total amount of all invoices
     * - $totalPaid: Total amount paid
     * - $totalOutstanding: Amount still owed
     * - $totalDiscounts: Total discounts given
     * - $averageTransactionValue: Average payment per transaction
     * - $transactionCount: Total number of transactions
     * - $collectionRate: Percentage of invoiced amount collected
     * - $reportRows: Aggregated report data by period
     * - $incomeSources: Breakdown by income type
     * - $topPayers: Top 5 students by payment amount
     * - $monthlyComparison: Monthly comparison data
     */
@endphp

<div class="filament-page">
    <div class="space-y-6">

        <!-- ================= HEADER SECTION ================= -->
        <div class="bg-white dark:bg-gray-800 border-b-4 border-primary-600 dark:border-primary-500 rounded-lg shadow-sm p-6">
            <div class="flex items-start justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Laporan Keuangan</h1>
                    <p class="text-gray-600 dark:text-gray-400 mt-1">PAUD UNDIP - Sistem Informasi Keuangan</p>
                    <div class="flex items-center gap-4 mt-3 text-sm text-gray-500 dark:text-gray-400">
                        <span class="flex items-center">
                            <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"/>
                            </svg>
                            {{ now()->format('d F Y, H:i') }} WIB
                        </span>
                        <span class="flex items-center px-3 py-1 bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 rounded-full">
                            <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M4 4a2 2 0 00-2 2v1h16V6a2 2 0 00-2-2H4z"/>
                                <path fill-rule="evenodd" d="M18 9H2v5a2 2 0 002 2h12a2 2 0 002-2V9zM4 13a1 1 0 011-1h1a1 1 0 110 2H5a1 1 0 01-1-1zm5-1a1 1 0 100 2h1a1 1 0 100-2H9z" clip-rule="evenodd"/>
                            </svg>
                            Pembayaran: Virtual Account BNI
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- ================= FILTER TOOLBAR ================= -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-5 border border-gray-200 dark:border-gray-700">
            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-4 uppercase tracking-wider">Filter Periode</h3>
            
            <div class="flex flex-col md:flex-row md:items-end gap-4">

                <div class="flex-1 grid grid-cols-1 md:grid-cols-3 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1.5 uppercase">
                            Tipe Periode
                        </label>
                        <select wire:model="granularity"
                            class="w-full border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 text-sm focus:ring-1 focus:ring-primary-500 focus:border-primary-500">
                            <option value="monthly">Bulanan</option>
                            <option value="yearly">Tahunan</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1.5 uppercase">
                            Bulan
                        </label>
                        <select wire:model="month"
                            class="w-full border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 text-sm focus:ring-1 focus:ring-primary-500 focus:border-primary-500 {{ $granularity === 'yearly' ? 'opacity-50' : '' }}"
                            @if($granularity === 'yearly') disabled @endif>
                            @foreach(range(1,12) as $m)
                                <option value="{{ $m }}">
                                    {{ DateTime::createFromFormat('!m', $m)->format('F') }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1.5 uppercase">
                            Tahun
                        </label>
                        <input type="number" wire:model="year" min="2020" max="2030"
                            class="w-full border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 text-sm focus:ring-1 focus:ring-primary-500 focus:border-primary-500" />
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <button wire:click="applyFilters"
                        class="px-4 py-2 bg-primary-600 hover:bg-primary-700 dark:bg-primary-500 dark:hover:bg-primary-600 text-white text-sm rounded-md font-medium transition-colors">
                        Terapkan Filter
                    </button>

                    <button wire:click="$refresh"
                        class="px-4 py-2 border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 text-sm rounded-md font-medium transition-colors">
                        Reset
                    </button>
                </div>

            </div>
        </div>

        <!-- ================= PRIMARY METRIC CARDS ================= -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">

            <!-- Total Pembayaran Card -->
            <div class="group p-5 bg-gradient-to-br from-blue-500 to-blue-600 dark:from-blue-600 dark:to-blue-700 rounded-xl shadow-lg hover:shadow-2xl transition-all duration-300 text-white">
                <div class="flex items-start justify-between">
                    <div class="flex-1">
                        <div class="flex items-center text-blue-100 text-sm font-medium mb-2">
                            <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M8.433 7.418c.155-.103.346-.196.567-.267v1.698a2.305 2.305 0 01-.567-.267C8.07 8.34 8 8.114 8 8c0-.114.07-.34.433-.582zM11 12.849v-1.698c.22.071.412.164.567.267.364.243.433.468.433.582 0 .114-.07.34-.433.582a2.305 2.305 0 01-.567.267z"/>
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v.092a4.535 4.535 0 00-1.676.662C6.602 6.234 6 7.009 6 8c0 .99.602 1.765 1.324 2.246.48.32 1.054.545 1.676.662v1.941c-.391-.127-.68-.317-.843-.504a1 1 0 10-1.51 1.31c.562.649 1.413 1.076 2.353 1.253V15a1 1 0 102 0v-.092a4.535 4.535 0 001.676-.662C13.398 13.766 14 12.991 14 12c0-.99-.602-1.765-1.324-2.246A4.535 4.535 0 0011 9.092V7.151c.391.127.68.317.843.504a1 1 0 101.511-1.31c-.563-.649-1.413-1.076-2.354-1.253V5z" clip-rule="evenodd"/>
                            </svg>
                            Total Pembayaran (Periode)
                        </div>
                        <div class="text-3xl font-bold mb-1">
                            Rp {{ number_format($currentPeriodTotal, 0, ',', '.') }}
                        </div>
                        <div class="flex items-center text-sm mt-2">
                            <span class="px-2 py-1 bg-white/20 rounded-lg font-semibold {{ $periodChangePercent >= 0 ? 'text-green-100' : 'text-red-100' }}">
                                {!! $periodChangePercent >= 0 ? '↗' : '↘' !!}
                                {{ abs($periodChangePercent) }}%
                            </span>
                            <span class="ml-2 text-blue-100">vs periode sebelumnya</span>
                        </div>
                    </div>
                    <div class="ml-2">
                        <div class="p-3 bg-white/20 rounded-lg">
                            <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M2 11a1 1 0 011-1h2a1 1 0 011 1v5a1 1 0 01-1 1H3a1 1 0 01-1-1v-5zM8 7a1 1 0 011-1h2a1 1 0 011 1v9a1 1 0 01-1 1H9a1 1 0 01-1-1V7zM14 4a1 1 0 011-1h2a1 1 0 011 1v12a1 1 0 01-1 1h-2a1 1 0 01-1-1V4z"/>
                            </svg>
                        </div>
                    </div>
                </div>
                
                <!-- Sparkline -->
                <div class="mt-4 h-12 bg-white/10 rounded-lg p-2">
                    @php
                        $vals = $sparkline;
                        $max = max($vals) ?: 1;
                        $points = collect($vals)->map(function($v, $i) use ($max, $vals) {
                            $x = ($i / max(count($vals)-1, 1)) * 100;
                            $y = 100 - (($v / $max) * 100);
                            return "$x,$y";
                        })->implode(' ');
                    @endphp
                    <svg class="w-full h-full" viewBox="0 0 100 100" preserveAspectRatio="none">
                        <polyline fill="none" stroke="rgba(255,255,255,0.8)" stroke-width="3" points="{{ $points }}" />
                    </svg>
                </div>
            </div>

            <!-- Total Tagihan Card -->
            <div class="group p-5 bg-gradient-to-br from-purple-500 to-purple-600 dark:from-purple-600 dark:to-purple-700 rounded-xl shadow-lg hover:shadow-2xl transition-all duration-300 text-white">
                <div class="flex items-start justify-between">
                    <div class="flex-1">
                        <div class="flex items-center text-purple-100 text-sm font-medium mb-2">
                            <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M4 4a2 2 0 00-2 2v4a2 2 0 002 2V6h10a2 2 0 00-2-2H4zm2 6a2 2 0 012-2h8a2 2 0 012 2v4a2 2 0 01-2 2H8a2 2 0 01-2-2v-4zm6 4a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/>
                            </svg>
                            Total Tagihan
                        </div>
                        <div class="text-3xl font-bold mb-1">
                            Rp {{ number_format($totalInvoiced, 0, ',', '.') }}
                        </div>
                        <div class="text-sm text-purple-100 mt-2">
                            Tingkat Koleksi: <span class="font-bold">{{ number_format($collectionRate, 1) }}%</span>
                        </div>
                    </div>
                    <div class="ml-2">
                        <div class="p-3 bg-white/20 rounded-lg">
                            <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M4 4a2 2 0 00-2 2v4a2 2 0 002 2V6h10a2 2 0 00-2-2H4zm2 6a2 2 0 012-2h8a2 2 0 012 2v4a2 2 0 01-2 2H8a2 2 0 01-2-2v-4zm6 4a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                    </div>
                </div>
                <div class="mt-4 bg-white/10 rounded-lg h-2">
                    <div class="bg-white h-2 rounded-lg transition-all duration-500" 
                         style="width: {{ min($collectionRate, 100) }}%">
                    </div>
                </div>
            </div>

            <!-- Total Outstanding Card -->
            <div class="group p-5 bg-gradient-to-br from-red-500 to-red-600 dark:from-red-600 dark:to-red-700 rounded-xl shadow-lg hover:shadow-2xl transition-all duration-300 text-white">
                <div class="flex items-start justify-between">
                    <div class="flex-1">
                        <div class="flex items-center text-red-100 text-sm font-medium mb-2">
                            <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                            </svg>
                            Total Outstanding
                        </div>
                        <div class="text-3xl font-bold mb-1">
                            Rp {{ number_format($totalOutstanding, 0, ',', '.') }}
                        </div>
                        <div class="text-sm text-red-100 mt-2">
                            @php 
                                $outstandingPercent = $totalInvoiced > 0 ? ($totalOutstanding / $totalInvoiced) * 100 : 0;
                            @endphp
                            {{ number_format($outstandingPercent, 1) }}% dari total tagihan
                        </div>
                    </div>
                    <div class="ml-2">
                        <div class="p-3 bg-white/20 rounded-lg">
                            <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Total Diskon Card -->
            <div class="group p-5 bg-gradient-to-br from-green-500 to-green-600 dark:from-green-600 dark:to-green-700 rounded-xl shadow-lg hover:shadow-2xl transition-all duration-300 text-white">
                <div class="flex items-start justify-between">
                    <div class="flex-1">
                        <div class="flex items-center text-green-100 text-sm font-medium mb-2">
                            <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M3 3a1 1 0 000 2v8a2 2 0 002 2h2.586l-1.293 1.293a1 1 0 101.414 1.414L10 15.414l2.293 2.293a1 1 0 001.414-1.414L12.414 15H15a2 2 0 002-2V5a1 1 0 100-2H3zm11.707 4.707a1 1 0 00-1.414-1.414L10 9.586 8.707 8.293a1 1 0 00-1.414 0l-2 2a1 1 0 101.414 1.414L8 10.414l1.293 1.293a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            Total Diskon Diberikan
                        </div>
                        <div class="text-3xl font-bold mb-1">
                            Rp {{ number_format($totalDiscounts, 0, ',', '.') }}
                        </div>
                        <div class="text-sm text-green-100 mt-2">
                            Insentif untuk siswa
                        </div>
                    </div>
                    <div class="ml-2">
                        <div class="p-3 bg-white/20 rounded-lg">
                            <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M3 3a1 1 0 000 2v8a2 2 0 002 2h2.586l-1.293 1.293a1 1 0 101.414 1.414L10 15.414l2.293 2.293a1 1 0 001.414-1.414L12.414 15H15a2 2 0 002-2V5a1 1 0 100-2H3zm11.707 4.707a1 1 0 00-1.414-1.414L10 9.586 8.707 8.293a1 1 0 00-1.414 0l-2 2a1 1 0 101.414 1.414L8 10.414l1.293 1.293a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- ================= SECONDARY METRIC CARDS ================= -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            
            <!-- Rata-rata Transaksi -->
            <div class="p-5 bg-white dark:bg-gray-800 rounded-lg shadow border-l-4 border-indigo-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400 font-medium">Rata-rata Transaksi</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-gray-100 mt-1">
                            Rp {{ number_format($averageTransactionValue, 0, ',', '.') }}
                        </p>
                    </div>
                    <div class="p-3 bg-indigo-100 dark:bg-indigo-900 rounded-lg">
                        <svg class="w-7 h-7 text-indigo-600 dark:text-indigo-400" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M2 11a1 1 0 011-1h2a1 1 0 011 1v5a1 1 0 01-1 1H3a1 1 0 01-1-1v-5zM8 7a1 1 0 011-1h2a1 1 0 011 1v9a1 1 0 01-1 1H9a1 1 0 01-1-1V7zM14 4a1 1 0 011-1h2a1 1 0 011 1v12a1 1 0 01-1 1h-2a1 1 0 01-1-1V4z"/>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Jumlah Transaksi -->
            <div class="p-5 bg-white dark:bg-gray-800 rounded-lg shadow border-l-4 border-yellow-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400 font-medium">Total Transaksi</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-gray-100 mt-1">
                            {{ number_format($transactionCount, 0, ',', '.') }}
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Transaksi pembayaran</p>
                    </div>
                    <div class="p-3 bg-yellow-100 dark:bg-yellow-900 rounded-lg">
                        <svg class="w-7 h-7 text-yellow-600 dark:text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"/>
                            <path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Periode Sebelumnya -->
            <div class="p-5 bg-white dark:bg-gray-800 rounded-lg shadow border-l-4 border-teal-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400 font-medium">Periode Sebelumnya</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-gray-100 mt-1">
                            Rp {{ number_format($previousPeriodTotal, 0, ',', '.') }}
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Untuk perbandingan</p>
                    </div>
                    <div class="p-3 bg-teal-100 dark:bg-teal-900 rounded-lg">
                        <svg class="w-7 h-7 text-teal-600 dark:text-teal-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                </div>
            </div>

        </div>

        <!-- ================= TWO COLUMN LAYOUT: REPORTS & CHARTS ================= -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            
            <!-- LEFT COLUMN: Laporan Agregat -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 border border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center">
                        <div class="p-2 bg-blue-100 dark:bg-blue-900 rounded-lg mr-3">
                            <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M6 2a2 2 0 00-2 2v12a2 2 0 002 2h8a2 2 0 002-2V7.414A2 2 0 0015.414 6L12 2.586A2 2 0 0010.586 2H6zm5 6a1 1 0 10-2 0v3.586l-1.293-1.293a1 1 0 10-1.414 1.414l3 3a1 1 0 001.414 0l3-3a1 1 0 00-1.414-1.414L11 11.586V8z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100">
                            Laporan Agregat
                        </h3>
                    </div>
                </div>

                <div class="overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50 dark:bg-gray-900">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                                        Periode
                                    </th>
                                    <th class="px-4 py-3 text-center text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                                        Transaksi
                                    </th>
                                    <th class="px-4 py-3 text-right text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                                        Total
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700 bg-white dark:bg-gray-800">
                                @forelse($reportRows as $r)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                                        <td class="px-4 py-3 text-gray-900 dark:text-gray-100 font-medium">
                                            @if($r['month'])
                                                <span class="inline-flex items-center">
                                                    <svg class="w-4 h-4 mr-1 text-gray-500" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"/>
                                                    </svg>
                                                    {{ DateTime::createFromFormat('!m', $r['month'])->format('F') }} {{ $r['year'] }}
                                                </span>
                                            @else
                                                <span class="inline-flex items-center">
                                                    <svg class="w-4 h-4 mr-1 text-gray-500" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"/>
                                                    </svg>
                                                    Tahun {{ $r['year'] }}
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            <span class="px-3 py-1 bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 rounded-full text-xs font-medium">
                                                {{ $r['count'] }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-right font-semibold text-gray-900 dark:text-gray-100">
                                            Rp {{ number_format($r['total_amount'], 0, ',', '.') }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="px-4 py-8 text-center">
                                            <svg class="w-12 h-12 mx-auto text-gray-400 dark:text-gray-600 mb-2" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                            </svg>
                                            <p class="text-gray-500 dark:text-gray-400 font-medium">Tidak ada data untuk filter ini</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- RIGHT COLUMN: Payment Methods -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 border border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center">
                        <div class="p-2 bg-green-100 dark:bg-green-900 rounded-lg mr-3">
                            <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M4 4a2 2 0 00-2 2v1h16V6a2 2 0 00-2-2H4z"/>
                                <path fill-rule="evenodd" d="M18 9H2v5a2 2 0 002 2h12a2 2 0 002-2V9zM4 13a1 1 0 011-1h1a1 1 0 110 2H5a1 1 0 01-1-1zm5-1a1 1 0 100 2h1a1 1 0 100-2H9z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100">
                            Metode Pembayaran
                        </h3>
                    </div>
                </div>

                <div class="space-y-4">
                    @forelse($paymentMethods as $pm)
                        @php 
                            $totalMethods = array_sum(array_column($paymentMethods, 'total'));
                            $percentage = $totalMethods > 0 ? ($pm['total'] / $totalMethods) * 100 : 0;
                        @endphp
                        <div class="p-4 bg-gray-50 dark:bg-gray-700 rounded-lg hover:shadow-md transition">
                            <div class="flex items-center justify-between mb-2">
                                <span class="font-medium text-gray-900 dark:text-gray-100">
                                    {{ ucfirst($pm['method']) }}
                                </span>
                                <span class="text-sm text-gray-600 dark:text-gray-400">
                                    {{ $pm['count'] }} transaksi
                                </span>
                            </div>
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-lg font-bold text-gray-900 dark:text-gray-100">
                                    Rp {{ number_format($pm['total'], 0, ',', '.') }}
                                </span>
                                <span class="text-sm font-semibold text-green-600 dark:text-green-400">
                                    {{ number_format($percentage, 1) }}%
                                </span>
                            </div>
                            <div class="w-full bg-gray-200 dark:bg-gray-600 rounded-full h-2">
                                <div class="bg-gradient-to-r from-green-400 to-green-600 h-2 rounded-full transition-all duration-500" 
                                     style="width: {{ $percentage }}%">
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="py-8 text-center">
                            <svg class="w-12 h-12 mx-auto text-gray-400 dark:text-gray-600 mb-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                            </svg>
                            <p class="text-gray-500 dark:text-gray-400">Tidak ada data metode pembayaran</p>
                        </div>
                    @endforelse
                </div>
            </div>

        </div>

        <!-- ================= SUMBER PEMASUKAN ================= -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center">
                    <div class="p-2 bg-purple-100 dark:bg-purple-900 rounded-lg mr-3">
                        <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M4 4a2 2 0 00-2 2v4a2 2 0 002 2V6h10a2 2 0 00-2-2H4zm2 6a2 2 0 012-2h8a2 2 0 012 2v4a2 2 0 01-2 2H8a2 2 0 01-2-2v-4zm6 4a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100">
                            Sumber Pemasukan
                        </h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                            Ringkasan berdasarkan jenis pendapatan (tidak termasuk diskon)
                        </p>
                    </div>
                </div>
            </div>

            <div class="overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-900">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                                    Sumber
                                </th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                                    Item
                                </th>
                                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                                    Total (Rp)
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                                    Persentase
                                </th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700 bg-white dark:bg-gray-800">
                            @php $grand = array_sum(array_column($incomeSources, 'total_amount') ?: [0]); @endphp

                            @forelse($incomeSources as $s)
                                @php $pct = $grand > 0 ? ($s['total_amount'] / $grand) * 100 : 0; @endphp

                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                                    <td class="px-4 py-4">
                                        <div class="flex items-center">
                                            <div class="w-2 h-2 rounded-full bg-purple-500 mr-2"></div>
                                            <span class="font-medium text-gray-900 dark:text-gray-100">
                                                {{ $s['income_type'] }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-4 text-center">
                                        <span class="px-3 py-1 bg-purple-100 dark:bg-purple-900 text-purple-800 dark:text-purple-200 rounded-full text-xs font-medium">
                                            {{ $s['items_count'] }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-4 text-right font-semibold text-gray-900 dark:text-gray-100">
                                        Rp {{ number_format($s['total_amount'], 0, ',', '.') }}
                                    </td>
                                    <td class="px-4 py-4">
                                        <div class="flex items-center space-x-2">
                                            <div class="flex-1 bg-gray-200 dark:bg-gray-600 rounded-full h-3">
                                                <div class="bg-gradient-to-r from-purple-400 to-purple-600 h-3 rounded-full transition-all duration-500"
                                                    style="width: {{ round($pct, 2) }}%">
                                                </div>
                                            </div>
                                            <span class="text-xs font-bold text-gray-600 dark:text-gray-400 min-w-[45px] text-right">
                                                {{ number_format($pct, 1) }}%
                                            </span>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-4 py-8 text-center">
                                        <svg class="w-12 h-12 mx-auto text-gray-400 dark:text-gray-600 mb-2" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                        </svg>
                                        <p class="text-gray-500 dark:text-gray-400 font-medium">Tidak ada data sumber pemasukan untuk filter ini</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                        
                        @if(count($incomeSources) > 0)
                        <tfoot class="bg-gray-50 dark:bg-gray-900">
                            <tr>
                                <td class="px-4 py-3 font-bold text-gray-900 dark:text-gray-100">TOTAL</td>
                                <td class="px-4 py-3 text-center font-bold text-gray-900 dark:text-gray-100">
                                    {{ array_sum(array_column($incomeSources, 'items_count')) }}
                                </td>
                                <td class="px-4 py-3 text-right font-bold text-gray-900 dark:text-gray-100">
                                    Rp {{ number_format($grand, 0, ',', '.') }}
                                </td>
                                <td class="px-4 py-3 text-right font-bold text-gray-900 dark:text-gray-100">
                                    100%
                                </td>
                            </tr>
                        </tfoot>
                        @endif
                    </table>
                </div>
            </div>
        </div>

        <!-- ================= TOP PAYERS ================= -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center">
                    <div class="p-2 bg-yellow-100 dark:bg-yellow-900 rounded-lg mr-3">
                        <svg class="w-6 h-6 text-yellow-600 dark:text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100">
                            Siswa dengan Pembayaran Terbanyak
                        </h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                            5 siswa teratas berdasarkan total pembayaran pada periode ini
                        </p>
                    </div>
                </div>
            </div>

            <div class="space-y-3">
                @forelse($topPayers as $index => $payer)
                    <div class="p-4 bg-gradient-to-r from-gray-50 to-white dark:from-gray-700 dark:to-gray-800 rounded-lg border border-gray-200 dark:border-gray-600 hover:shadow-md transition">
                        <div class="flex items-center">
                            <!-- Rank Badge -->
                            <div class="mr-4">
                                <div class="w-10 h-10 flex items-center justify-center rounded-full font-bold text-white
                                    {{ $index === 0 ? 'bg-gradient-to-br from-yellow-400 to-yellow-600' : '' }}
                                    {{ $index === 1 ? 'bg-gradient-to-br from-gray-300 to-gray-500' : '' }}
                                    {{ $index === 2 ? 'bg-gradient-to-br from-orange-400 to-orange-600' : '' }}
                                    {{ $index > 2 ? 'bg-gradient-to-br from-blue-400 to-blue-600' : '' }}">
                                    {{ $index + 1 }}
                                </div>
                            </div>
                            
                            <!-- Student Info -->
                            <div class="flex-1">
                                <div class="flex items-center justify-between mb-1">
                                    <h4 class="font-bold text-gray-900 dark:text-gray-100 text-lg">
                                        {{ $payer['name'] }}
                                    </h4>
                                    <span class="text-xs bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 px-2 py-1 rounded">
                                        NIS: {{ $payer['nis'] }}
                                    </span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-sm text-gray-600 dark:text-gray-400">
                                        {{ $payer['payment_count'] }} transaksi
                                    </span>
                                    <span class="text-lg font-bold text-gray-900 dark:text-gray-100">
                                        Rp {{ number_format($payer['total_paid'], 0, ',', '.') }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="py-8 text-center">
                        <svg class="w-12 h-12 mx-auto text-gray-400 dark:text-gray-600 mb-2" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"/>
                        </svg>
                        <p class="text-gray-500 dark:text-gray-400 font-medium">Tidak ada data pembayaran untuk periode ini</p>
                    </div>
                @endforelse
            </div>
        </div>

        <!-- ================= EXPORT & ACTIONS ================= -->
        <div class="bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-800 dark:to-gray-900 rounded-xl shadow-lg p-6 border border-gray-200 dark:border-gray-700">
            <div class="flex flex-col md:flex-row items-center justify-between gap-4">
                <div class="flex items-center">
                    <div class="p-3 bg-primary-100 dark:bg-primary-900 rounded-lg mr-4">
                        <svg class="w-8 h-8 text-primary-600 dark:text-primary-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100">Export Laporan</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Unduh laporan dalam berbagai format</p>
                    </div>
                </div>
                
                <div class="flex flex-wrap gap-3">
                    <button wire:click="exportPdf" 
                        class="px-6 py-3 bg-red-600 hover:bg-red-700 dark:bg-red-500 dark:hover:bg-red-600 text-white rounded-lg font-medium shadow-lg hover:shadow-xl transition-all duration-200 flex items-center group">
                        <svg class="w-5 h-5 mr-2 group-hover:scale-110 transition-transform" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z" clip-rule="evenodd"/>
                        </svg>
                        Export PDF
                    </button>
                    
                    <button wire:click="exportExcel" 
                        class="px-6 py-3 bg-green-600 hover:bg-green-700 dark:bg-green-500 dark:hover:bg-green-600 text-white rounded-lg font-medium shadow-lg hover:shadow-xl transition-all duration-200 flex items-center group">
                        <svg class="w-5 h-5 mr-2 group-hover:scale-110 transition-transform" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM6.293 6.707a1 1 0 010-1.414l3-3a1 1 0 011.414 0l3 3a1 1 0 01-1.414 1.414L11 5.414V13a1 1 0 11-2 0V5.414L7.707 6.707a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                        </svg>
                        Export Excel
                    </button>
                    
                    <button onclick="window.print()" 
                        class="px-6 py-3 bg-blue-600 hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600 text-white rounded-lg font-medium shadow-lg hover:shadow-xl transition-all duration-200 flex items-center group">
                        <svg class="w-5 h-5 mr-2 group-hover:scale-110 transition-transform" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M5 4v3H4a2 2 0 00-2 2v3a2 2 0 002 2h1v2a2 2 0 002 2h6a2 2 0 002-2v-2h1a2 2 0 002-2V9a2 2 0 00-2-2h-1V4a2 2 0 00-2-2H7a2 2 0 00-2 2zm8 0H7v3h6V4zm0 8H7v4h6v-4z" clip-rule="evenodd"/>
                        </svg>
                        Print
                    </button>
                    
                    <button wire:click="$refresh" 
                        class="px-6 py-3 bg-gray-700 hover:bg-gray-800 dark:bg-gray-600 dark:hover:bg-gray-700 text-white rounded-lg font-medium shadow-lg hover:shadow-xl transition-all duration-200 flex items-center group">
                        <svg class="w-5 h-5 mr-2 group-hover:rotate-180 transition-transform duration-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z" clip-rule="evenodd"/>
                        </svg>
                        Refresh
                    </button>
                </div>
            </div>
        </div>

        <!-- ================= FOOTER INFO ================= -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 border border-gray-200 dark:border-gray-700">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="flex items-start">
                    <svg class="w-5 h-5 text-primary-600 dark:text-primary-400 mr-3 mt-1 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                    </svg>
                    <div>
                        <h4 class="font-semibold text-gray-900 dark:text-gray-100 mb-1">Tentang Laporan</h4>
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            Laporan ini menampilkan analisis keuangan komprehensif berdasarkan data pembayaran dan tagihan siswa PAUD UNDIP.
                        </p>
                    </div>
                </div>
                
                <div class="flex items-start">
                    <svg class="w-5 h-5 text-primary-600 dark:text-primary-400 mr-3 mt-1 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.38z" clip-rule="evenodd"/>
                    </svg>
                    <div>
                        <h4 class="font-semibold text-gray-900 dark:text-gray-100 mb-1">Data Real-time</h4>
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            Semua data yang ditampilkan adalah data real-time dari sistem dan diperbarui secara otomatis.
                        </p>
                    </div>
                </div>
                
                <div class="flex items-start">
                    <svg class="w-5 h-5 text-primary-600 dark:text-primary-400 mr-3 mt-1 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M2.166 4.999A11.954 11.954 0 0010 1.944 11.954 11.954 0 0017.834 5c.11.65.166 1.32.166 2.001 0 5.225-3.34 9.67-8 11.317C5.34 16.67 2 12.225 2 7c0-.682.057-1.35.166-2.001zm11.541 3.708a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    <div>
                        <h4 class="font-semibold text-gray-900 dark:text-gray-100 mb-1">Keamanan Data</h4>
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            Data keuangan dilindungi dengan enkripsi dan hanya dapat diakses oleh pengguna yang berwenang.
                        </p>
                    </div>
                </div>
            </div>
            
            <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                <div class="flex flex-col md:flex-row items-center justify-between gap-4">
                    <div class="flex items-center text-sm text-gray-600 dark:text-gray-400">
                        <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                        </svg>
                        Terakhir diperbarui: {{ now()->format('d F Y, H:i:s') }} WIB
                    </div>
                    
                    <div class="flex items-center gap-3">
                        <span class="text-sm text-gray-600 dark:text-gray-400">Powered by</span>
                        <span class="font-bold text-primary-600 dark:text-primary-400">PAUD UNDIP</span>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

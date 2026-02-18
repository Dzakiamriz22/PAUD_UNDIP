@php
    /**
     * Financial Report Page - PAUD UNDIP
     * Sistem Pembayaran: Virtual Account BNI
     *  
     * Variables: $totalInvoiced, $totalPaid, $totalOutstanding, $totalDiscounts,
     * $averageTransactionValue, $transactionCount, $collectionRate,
     * $reportRows, $incomeSources, $topPayers, $sparkline
     */
@endphp

<div class="filament-page">
    <div class="space-y-5">

        <!-- Header -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-5 border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between mb-3">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Laporan Keuangan</h1>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">PAUD UNDIP • Sistem Pembayaran Virtual Account BNI</p>
                </div>
                <div class="flex items-center gap-3 text-xs">
                    <span class="px-3 py-1.5 bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 rounded-md font-medium">
                        Metode: VA BNI
                    </span>
                    <span class="text-gray-500 dark:text-gray-400">
                        {{ now()->format('d M Y, H:i') }}
                    </span>
                </div>
            </div>
            
            <!-- Info Box - Penjelasan Laporan -->
            <div class="mt-3 p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
                <div class="flex items-start gap-2">
                    <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                    </svg>
                    <div class="text-xs text-blue-900 dark:text-blue-200">
                        <p class="font-semibold mb-1">Tentang Laporan Ini:</p>
                        <ul class="space-y-0.5 list-disc list-inside">
                            <li><strong>Pembayaran</strong> = jumlah yang sudah diterima dan tercatat dalam sistem</li>
                            <li><strong>Total Tagihan</strong> = jumlah semua invoice yang diterbitkan</li>
                            <li><strong>Tunggakan</strong> = tagihan yang belum dibayar</li>
                            <li><strong>Tingkat Koleksi</strong> = persentase tagihan yang sudah terbayar</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4 border border-gray-200 dark:border-gray-700">
            <div class="flex items-start justify-between mb-3">
                <div>
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Filter Laporan Keuangan</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Filter data berdasarkan periode, jenis pendapatan, kelas, status, dan tahun anggaran</p>
                </div>
            </div>
            
            <!-- Periode Filter -->
            <div class="mb-3 pb-3 border-b border-gray-200 dark:border-gray-700">
                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-2">Periode Transaksi</label>
                <div class="grid grid-cols-3 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">
                            Periode
                            <span class="text-gray-400 dark:text-gray-500" title="Pilih tampilan bulanan atau tahunan">ⓘ</span>
                        </label>
                        <select wire:model="granularity" class="w-full border-gray-300 dark:border-gray-600 rounded-md px-3 py-1.5 text-sm bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100">
                            <option value="monthly">Bulanan</option>
                            <option value="yearly">Tahunan</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">
                            Bulan
                            <span class="text-gray-400 dark:text-gray-500" title="Aktif hanya untuk periode bulanan">ⓘ</span>
                        </label>
                        <select wire:model="month" class="w-full border-gray-300 dark:border-gray-600 rounded-md px-3 py-1.5 text-sm bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 {{ $granularity === 'yearly' ? 'opacity-50' : '' }}" @if($granularity === 'yearly') disabled @endif>
                            @foreach(range(1,12) as $m)
                                <option value="{{ $m }}">{{ \DateTime::createFromFormat('!m', $m)->format('F') }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">
                            Tahun
                            <span class="text-gray-400 dark:text-gray-500" title="Tahun kalender">ⓘ</span>
                        </label>
                        <input type="number" wire:model="year" min="2020" max="2030" class="w-full border-gray-300 dark:border-gray-600 rounded-md px-3 py-1.5 text-sm bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100" />
                    </div>
                </div>
            </div>

            <!-- Additional Filters -->
            <div class="mb-3">
                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-2">Filter Kategori</label>
                <div class="grid grid-cols-4 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">
                            Jenis Pendapatan
                            <span class="text-gray-400 dark:text-gray-500" title="Filter berdasarkan jenis pendapatan">ⓘ</span>
                        </label>
                        <select wire:model="incomeTypeId" class="w-full border-gray-300 dark:border-gray-600 rounded-md px-3 py-1.5 text-sm bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100">
                            <option value="">Semua Jenis</option>
                            @foreach($this->incomeTypes as $incomeType)
                                <option value="{{ $incomeType->id }}">{{ $incomeType->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">
                            Kelas
                            <span class="text-gray-400 dark:text-gray-500" title="Filter berdasarkan kelas siswa">ⓘ</span>
                        </label>
                        <select wire:model="classId" class="w-full border-gray-300 dark:border-gray-600 rounded-md px-3 py-1.5 text-sm bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100">
                            <option value="">Semua Kelas</option>
                            @foreach($this->classes as $class)
                                <option value="{{ $class->id }}">{{ $class->code }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">
                            Status Pembayaran
                            <span class="text-gray-400 dark:text-gray-500" title="Filter berdasarkan status tagihan">ⓘ</span>
                        </label>
                        <select wire:model="status" class="w-full border-gray-300 dark:border-gray-600 rounded-md px-3 py-1.5 text-sm bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100">
                            <option value="all">Semua Status</option>
                            <option value="paid">Lunas</option>
                            <option value="unpaid">Belum Bayar</option>
                            <option value="partial">Sebagian</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">
                            Tahun Anggaran
                            <span class="text-gray-400 dark:text-gray-500" title="Tahun anggaran akademik">ⓘ</span>
                        </label>
                        <select wire:model="academicYearId" class="w-full border-gray-300 dark:border-gray-600 rounded-md px-3 py-1.5 text-sm bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100">
                            <option value="">Semua Tahun</option>
                            @foreach($this->academicYears as $ay)
                                <option value="{{ $ay->id }}">{{ $ay->year }} - {{ ucfirst($ay->semester) }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex gap-2 items-center">
                <button wire:click="applyFiltersAction" class="px-4 py-1.5 bg-primary-600 hover:bg-primary-700 text-white text-sm rounded-md font-medium transition flex items-center gap-2">
                    <span wire:loading.remove wire:target="applyFiltersAction">Terapkan Filter</span>
                    <span wire:loading wire:target="applyFiltersAction" class="flex items-center gap-2">
                        <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Memuat...
                    </span>
                </button>
                <button wire:click="resetFilters" class="px-4 py-1.5 border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 text-sm rounded-md font-medium transition">Reset</button>
            </div>
        </div>

        <!-- Loading Overlay -->
        <div wire:loading wire:target="applyFiltersAction" class="fixed inset-0 bg-black bg-opacity-10 z-50 flex items-center justify-center pointer-events-none">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl p-4 flex items-center gap-3">
                <svg class="animate-spin h-6 w-6 text-primary-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Memuat data...</span>
            </div>
        </div>

        <!-- Metrics -->
        <div class="grid grid-cols-4 gap-4 transition-opacity duration-200" wire:loading.class="opacity-50" wire:target="applyFiltersAction">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4 border border-gray-200 dark:border-gray-700">
                <div class="flex items-start justify-between mb-2">
                    <div class="flex items-center gap-1">
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Pembayaran</p>
                        <span class="group relative">
                            <svg class="w-3.5 h-3.5 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 cursor-help" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                            </svg>
                            <span class="invisible group-hover:visible absolute left-0 top-full mt-1 w-48 p-2 bg-gray-900 text-white text-xs rounded shadow-lg z-10">
                                Total pembayaran yang sudah diterima dalam periode ini melalui VA BNI
                            </span>
                        </span>
                    </div>
                    <div class="p-1.5 bg-blue-50 dark:bg-blue-900/20 rounded">
                        <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="currentColor" viewBox="0 0 20 20"><path d="M8.433 7.418c.155-.103.346-.196.567-.267v1.698a2.305 2.305 0 01-.567-.267C8.07 8.34 8 8.114 8 8c0-.114.07-.34.433-.582zM11 12.849v-1.698c.22.071.412.164.567.267.364.243.433.468.433.582 0 .114-.07.34-.433.582a2.305 2.305 0 01-.567.267z"/><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v.092a4.535 4.535 0 00-1.676.662C6.602 6.234 6 7.009 6 8c0 .99.602 1.765 1.324 2.246.48.32 1.054.545 1.676.662v1.941c-.391-.127-.68-.317-.843-.504a1 1 0 10-1.51 1.31c.562.649 1.413 1.076 2.353 1.253V15a1 1 0 102 0v-.092a4.535 4.535 0 001.676-.662C13.398 13.766 14 12.991 14 12c0-.99-.602-1.765-1.324-2.246A4.535 4.535 0 0011 9.092V7.151c.391.127.68.317.843.504a1 1 0 101.511-1.31c-.563-.649-1.413-1.076-2.354-1.253V5z" clip-rule="evenodd"/></svg>
                    </div>
                </div>
                <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">Rp {{ number_format($currentPeriodTotal, 0, ',', '.') }}</p>
                <div class="mt-2 pt-2 border-t border-gray-100 dark:border-gray-700 text-xs">
                    <span class="font-medium {{ $periodChangePercent >= 0 ? 'text-green-600' : 'text-red-600' }}">{{ $periodChangePercent >= 0 ? '↗' : '↘' }} {{ number_format(abs($periodChangePercent), 1) }}%</span>
                    <span class="text-gray-500 dark:text-gray-400 ml-1">vs periode lalu</span>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4 border border-gray-200 dark:border-gray-700">
                <div class="flex items-start justify-between mb-2">
                    <div class="flex items-center gap-1">
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Total Tagihan</p>
                        <span class="group relative">
                            <svg class="w-3.5 h-3.5 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 cursor-help" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                            </svg>
                            <span class="invisible group-hover:visible absolute left-0 top-full mt-1 w-48 p-2 bg-gray-900 text-white text-xs rounded shadow-lg z-10">
                                Jumlah seluruh invoice yang diterbitkan. Tingkat koleksi menunjukkan persentase yang sudah terbayar
                            </span>
                        </span>
                    </div>
                    <div class="p-1.5 bg-purple-50 dark:bg-purple-900/20 rounded">
                        <svg class="w-4 h-4 text-purple-600 dark:text-purple-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4 4a2 2 0 00-2 2v4a2 2 0 002 2V6h10a2 2 0 00-2-2H4zm2 6a2 2 0 012-2h8a2 2 0 012 2v4a2 2 0 01-2 2H8a2 2 0 01-2-2v-4zm6 4a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/></svg>
                    </div>
                </div>
                <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">Rp {{ number_format($totalInvoiced, 0, ',', '.') }}</p>
                <div class="mt-2 pt-2 border-t border-gray-100 dark:border-gray-700 text-xs">
                    <span class="text-gray-500 dark:text-gray-400">Koleksi:</span>
                    <span class="font-semibold text-gray-900 dark:text-gray-100 ml-1">{{ number_format($collectionRate, 1) }}%</span>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4 border border-gray-200 dark:border-gray-700">
                <div class="flex items-start justify-between mb-2">
                    <div class="flex items-center gap-1">
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Tunggakan</p>
                        <span class="group relative">
                            <svg class="w-3.5 h-3.5 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 cursor-help" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                            </svg>
                            <span class="invisible group-hover:visible absolute left-0 top-full mt-1 w-48 p-2 bg-gray-900 text-white text-xs rounded shadow-lg z-10">
                                Invoice yang sudah diterbitkan tetapi belum dibayar oleh siswa/orang tua
                            </span>
                        </span>
                    </div>
                    <div class="p-1.5 bg-red-50 dark:bg-red-900/20 rounded">
                        <svg class="w-4 h-4 text-red-600 dark:text-red-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                    </div>
                </div>
                <p class="text-2xl font-bold text-red-600 dark:text-red-400">Rp {{ number_format($totalOutstanding, 0, ',', '.') }}</p>
                <div class="mt-2 pt-2 border-t border-gray-100 dark:border-gray-700 text-xs text-gray-500 dark:text-gray-400">
                    @php $outstandingPercent = $totalInvoiced > 0 ? ($totalOutstanding / $totalInvoiced) * 100 : 0; @endphp
                    {{ number_format($outstandingPercent, 1) }}% dari total
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4 border border-gray-200 dark:border-gray-700">
                <div class="flex items-start justify-between mb-2">
                    <div class="flex items-center gap-1">
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Diskon</p>
                        <span class="group relative">
                            <svg class="w-3.5 h-3.5 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 cursor-help" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                            </svg>
                            <span class="invisible group-hover:visible absolute left-0 top-full mt-1 w-48 p-2 bg-gray-900 text-white text-xs rounded shadow-lg z-10">
                                Potongan harga yang diberikan kepada siswa (misal: diskon alumni, diskon anak kembar, dll)
                            </span>
                        </span>
                    </div>
                    <div class="p-1.5 bg-green-50 dark:bg-green-900/20 rounded">
                        <svg class="w-4 h-4 text-green-600 dark:text-green-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M3 3a1 1 0 000 2v8a2 2 0 002 2h2.586l-1.293 1.293a1 1 0 101.414 1.414L10 15.414l2.293 2.293a1 1 0 001.414-1.414L12.414 15H15a2 2 0 002-2V5a1 1 0 100-2H3zm11.707 4.707a1 1 0 00-1.414-1.414L10 9.586 8.707 8.293a1 1 0 00-1.414 0l-2 2a1 1 0 101.414 1.414L8 10.414l1.293 1.293a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                    </div>
                </div>
                <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">Rp {{ number_format($totalDiscounts, 0, ',', '.') }}</p>
                <div class="mt-2 pt-2 border-t border-gray-100 dark:border-gray-700 text-xs text-gray-500 dark:text-gray-400">
                    Potongan harga
                </div>
            </div>
        </div>

        <!-- Secondary Metrics -->
        <div class="grid grid-cols-3 gap-4 transition-opacity duration-200" wire:loading.class="opacity-50" wire:target="applyFiltersAction">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-3 border border-gray-200 dark:border-gray-700">
                <div class="flex items-center gap-1 mb-1">
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Rata-rata Transaksi</p>
                    <span class="group relative">
                        <svg class="w-3 h-3 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 cursor-help" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                        </svg>
                        <span class="invisible group-hover:visible absolute left-0 top-full mt-1 w-40 p-2 bg-gray-900 text-white text-xs rounded shadow-lg z-10">
                            Nilai rata-rata per transaksi pembayaran
                        </span>
                    </span>
                </div>
                <p class="text-lg font-bold text-gray-900 dark:text-gray-100">Rp {{ number_format($averageTransactionValue, 0, ',', '.') }}</p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-3 border border-gray-200 dark:border-gray-700">
                <div class="flex items-center gap-1 mb-1">
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Total Transaksi</p>
                    <span class="group relative">
                        <svg class="w-3 h-3 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 cursor-help" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                        </svg>
                        <span class="invisible group-hover:visible absolute left-0 top-full mt-1 w-40 p-2 bg-gray-900 text-white text-xs rounded shadow-lg z-10">
                            Jumlah transaksi pembayaran yang tercatat
                        </span>
                    </span>
                </div>
                <p class="text-lg font-bold text-gray-900 dark:text-gray-100">{{ number_format($transactionCount, 0, ',', '.') }} transaksi</p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-3 border border-gray-200 dark:border-gray-700">
                <div class="flex items-center gap-1 mb-1">
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Periode Sebelumnya</p>
                    <span class="group relative">
                        <svg class="w-3 h-3 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 cursor-help" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                        </svg>
                        <span class="invisible group-hover:visible absolute left-0 top-full mt-1 w-40 p-2 bg-gray-900 text-white text-xs rounded shadow-lg z-10">
                            Pembayaran periode sebelumnya untuk perbandingan
                        </span>
                    </span>
                </div>
                <p class="text-lg font-bold text-gray-900 dark:text-gray-100">Rp {{ number_format($previousPeriodTotal, 0, ',', '.') }}</p>
            </div>
        </div>

        <!-- Reports & Income Sources -->
        <div class="grid grid-cols-2 gap-5 transition-opacity duration-200" wire:loading.class="opacity-50" wire:target="applyFiltersAction">
            <!-- Laporan Agregat -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100 uppercase">Laporan Agregat</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Klik pada baris untuk melihat detail transaksi</p>
                </div>
                <div class="p-4">
                    <table class="w-full text-sm">
                        <thead class="text-xs text-gray-500 dark:text-gray-400 border-b border-gray-200 dark:border-gray-700">
                            <tr>
                                <th class="py-2 text-left font-medium">Periode</th>
                                <th class="py-2 text-center font-medium">Transaksi</th>
                                <th class="py-2 text-right font-medium">Total</th>
                                <th class="py-2 w-8"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @forelse($reportRows as $index => $r)
                                <tr>
                                    <td class="py-2.5 text-gray-900 dark:text-gray-100 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700/50" onclick="toggleDetail('detail-{{ $index }}')">
                                        @if($r['month'])
                                            {{ \DateTime::createFromFormat('!m', $r['month'])->format('M') }} {{ $r['year'] }}
                                        @else
                                            {{ $r['year'] }}
                                        @endif
                                    </td>
                                    <td class="py-2.5 text-center cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700/50" onclick="toggleDetail('detail-{{ $index }}')">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                            {{ $r['count'] }} transaksi
                                        </span>
                                    </td>
                                    <td class="py-2.5 text-right font-semibold text-gray-900 dark:text-gray-100 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700/50" onclick="toggleDetail('detail-{{ $index }}')">
                                        Rp {{ number_format($r['total_amount'], 0, ',', '.') }}
                                    </td>
                                    <td class="py-2.5 text-center">
                                        <button onclick="toggleDetail('detail-{{ $index }}')" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-all duration-200 focus:outline-none" id="arrow-{{ $index }}">
                                            <svg class="w-5 h-5 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                            </svg>
                                        </button>
                                    </td>
                                </tr>
                                <tr id="detail-{{ $index }}" style="display: none;">
                                    <td colspan="4" class="p-0 bg-gray-50 dark:bg-gray-900/50">
                                        <div class="px-4 py-3">
                                            <div class="mb-2 flex items-center gap-2 text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                                </svg>
                                                Detail Transaksi ({{ count($r['details'] ?? []) }})
                                                @if(!isset($r['details']))
                                                    <span class="text-red-500 text-xs">[DEBUG: No details key]</span>
                                                @endif
                                            </div>
                                            @if(isset($r['details']) && count($r['details']) > 0)
                                                <div class="space-y-2 max-h-96 overflow-y-auto">
                                                    @foreach($r['details'] as $detail)
                                                        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-3 hover:shadow-md transition-shadow">
                                                            <div class="flex items-start justify-between">
                                                                <div class="flex-1">
                                                                    <div class="flex items-center gap-2 mb-1">
                                                                        <span class="font-semibold text-sm text-gray-900 dark:text-gray-100">
                                                                            {{ $detail['receipt_number'] }}
                                                                        </span>
                                                                        @if($detail['class_code'])
                                                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200">
                                                                                {{ $detail['class_code'] }}
                                                                            </span>
                                                                        @endif
                                                                    </div>
                                                                    <div class="text-xs text-gray-600 dark:text-gray-400 space-y-0.5">
                                                                        <div class="flex items-center gap-1.5">
                                                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                                                            </svg>
                                                                            <span class="font-medium">{{ $detail['student_name'] }}</span>
                                                                        </div>
                                                                        <div class="flex items-center gap-1.5">
                                                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                                                            </svg>
                                                                            <span>{{ \Carbon\Carbon::parse($detail['payment_date'])->format('d M Y') }}</span>
                                                                        </div>
                                                                        @if($detail['invoice_number'])
                                                                            <div class="flex items-center gap-1.5 text-gray-500 dark:text-gray-500">
                                                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                                                                </svg>
                                                                                <span>Invoice: {{ $detail['invoice_number'] }}</span>
                                                                            </div>
                                                                        @endif
                                                                    </div>
                                                                </div>
                                                                <div class="ml-3 text-right">
                                                                    <div class="text-sm font-bold text-green-600 dark:text-green-400">
                                                                        Rp {{ number_format($detail['amount_paid'], 0, ',', '.') }}
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @else
                                                <p class="text-xs text-gray-500 dark:text-gray-400 italic">Tidak ada detail transaksi</p>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="py-4 text-center text-gray-500 dark:text-gray-400">Tidak ada data</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            
            <script>
                function toggleDetail(detailId) {
                    console.log('toggleDetail called with:', detailId);
                    const detailRow = document.getElementById(detailId);
                    const arrowId = detailId.replace('detail-', 'arrow-');
                    const arrow = document.getElementById(arrowId);
                    
                    console.log('detailRow found:', !!detailRow);
                    console.log('arrow found:', !!arrow);
                    
                    if (!detailRow) {
                        console.error('Detail row not found:', detailId);
                        return;
                    }
                    
                    if (detailRow.style.display === 'none' || detailRow.style.display === '') {
                        detailRow.style.display = 'table-row';
                        console.log('Showing detail row');
                        if (arrow) {
                            const svg = arrow.querySelector('svg');
                            if (svg) svg.style.transform = 'rotate(180deg)';
                        }
                    } else {
                        detailRow.style.display = 'none';
                        console.log('Hiding detail row');
                        if (arrow) {
                            const svg = arrow.querySelector('svg');
                            if (svg) svg.style.transform = 'rotate(0deg)';
                        }
                    }
                }
                
                // Test function on page load
                console.log('Financial report detail toggle script loaded');
            </script>

            <!-- Sumber Pemasukan -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100 uppercase">Sumber Pemasukan</h3>
                </div>
                <div class="p-4">
                    <table class="w-full text-sm">
                        <thead class="text-xs text-gray-500 dark:text-gray-400 border-b border-gray-200 dark:border-gray-700">
                            <tr>
                                <th class="py-2 text-left font-medium">Sumber</th>
                                <th class="py-2 text-center font-medium">Item</th>
                                <th class="py-2 text-right font-medium">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @php $grand = array_sum(array_column($incomeSources, 'total_amount') ?: [0]); @endphp
                            @forelse($incomeSources as $s)
                                @php $pct = $grand > 0 ? ($s['total_amount'] / $grand) * 100 : 0; @endphp
                                <tr>
                                    <td class="py-2">
                                        <div class="text-gray-900 dark:text-gray-100 font-medium">{{ $s['income_type'] }}</div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">{{ number_format($pct, 1) }}%</div>
                                    </td>
                                    <td class="py-2 text-center text-gray-700 dark:text-gray-300">{{ $s['items_count'] }}</td>
                                    <td class="py-2 text-right font-semibold text-gray-900 dark:text-gray-100">
                                        Rp {{ number_format($s['total_amount'], 0, ',', '.') }}
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="py-4 text-center text-gray-500 dark:text-gray-400">Tidak ada data</td></tr>
                            @endforelse
                            @if(count($incomeSources) > 0)
                                <tr class="font-bold bg-gray-50 dark:bg-gray-900">
                                    <td class="py-2 text-gray-900 dark:text-gray-100">TOTAL</td>
                                    <td class="py-2 text-center text-gray-900 dark:text-gray-100">{{ array_sum(array_column($incomeSources, 'items_count')) }}</td>
                                    <td class="py-2 text-right text-gray-900 dark:text-gray-100">Rp {{ number_format($grand, 0, ',', '.') }}</td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Collection by Class -->
        @if(!empty($collectionByClass))
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 transition-opacity duration-200" wire:loading.class="opacity-50" wire:target="applyFiltersAction">
            <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100 uppercase">Ringkasan Koleksi per Kelas</h3>
            </div>
            <div class="p-4">
                <table class="w-full text-sm">
                    <thead class="text-xs text-gray-500 dark:text-gray-400 border-b border-gray-200 dark:border-gray-700">
                        <tr>
                            <th class="py-2 text-left font-medium">Kelas</th>
                            <th class="py-2 text-right font-medium">Total Tagihan</th>
                            <th class="py-2 text-right font-medium">Pembayaran</th>
                            <th class="py-2 text-right font-medium">Tunggakan</th>
                            <th class="py-2 text-center font-medium">Tingkat Koleksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @foreach($collectionByClass as $class)
                            <tr>
                                <td class="py-2 text-gray-900 dark:text-gray-100 font-medium">{{ $class['class_name'] }}</td>
                                <td class="py-2 text-right text-gray-900 dark:text-gray-100">
                                    Rp {{ number_format($class['total_invoiced'], 0, ',', '.') }}
                                </td>
                                <td class="py-2 text-right text-green-600 dark:text-green-400 font-semibold">
                                    Rp {{ number_format($class['total_paid'], 0, ',', '.') }}
                                </td>
                                <td class="py-2 text-right text-red-600 dark:text-red-400">
                                    Rp {{ number_format($class['outstanding'], 0, ',', '.') }}
                                </td>
                                <td class="py-2 text-center">
                                    <span class="px-2 py-1 rounded-full text-xs font-semibold
                                        @switch(true)
                                            @case($class['collection_rate'] >= 85)
                                                bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300
                                                @break
                                            @case($class['collection_rate'] >= 70)
                                                bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-300
                                                @break
                                            @default
                                                bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300
                                        @endswitch
                                    ">
                                        {{ number_format($class['collection_rate'], 1) }}%
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        <!-- Actions -->
        <div class="flex items-center justify-between bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4 border border-gray-200 dark:border-gray-700">
            <div class="text-sm text-gray-600 dark:text-gray-400">
                <p class="font-medium text-gray-900 dark:text-gray-100">Export Laporan</p>
                <p class="text-xs mt-0.5">Unduh laporan dalam format PDF atau Excel</p>
            </div>
            <div class="flex gap-2">
                <button wire:click="exportPdf" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm rounded-md font-medium transition flex items-center gap-2">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z" clip-rule="evenodd"/></svg>
                    PDF
                </button>
                <button wire:click="exportExcel" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm rounded-md font-medium transition flex items-center gap-2">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM6.293 6.707a1 1 0 010-1.414l3-3a1 1 0 011.414 0l3 3a1 1 0 01-1.414 1.414L11 5.414V13a1 1 0 11-2 0V5.414L7.707 6.707a1 1 0 01-1.414 0z" clip-rule="evenodd"/></svg>
                    Excel
                </button>
            </div>
        </div>

    </div>
</div>

@props(['item', 'type', 'bookingMode'])

<div class="py-3 border-b border-dashed border-slate-200 dark:border-slate-700 last:border-0">
    <div class="flex justify-between items-start gap-4">
        {{-- Kiri: Nama & Info --}}
        <div class="flex items-start gap-3 flex-1">
            <div class="mt-0.5 text-slate-400 dark:text-slate-500">
                <i data-feather="{{ $type === 'venue' ? 'home' : 'package' }}" class="size-4"></i>
            </div>
            <div>
                <p class="text-sm font-semibold text-slate-800 dark:text-slate-200">
                    {{ $item->product->product_name ?? 'Unknown Item' }}
                </p>
                <div class="flex flex-wrap items-center gap-x-2 text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                    {{-- Tanggal Ringkas --}}
                    <span>
                        @if ($bookingMode === 'hourly')
                            {{ $item->start_date_request?->format('d M, H:i') }} - {{ $item->end_date_request?->format('H:i') }}
                        @else
                            {{ $item->start_date_request?->format('d M') }} - {{ $item->end_date_request?->format('d M Y') }}
                        @endif
                    </span>
                    <span class="text-slate-300">•</span>
                    {{-- Durasi --}}
                    <span>
                        @if ($bookingMode === 'hourly')
                            {{ \Carbon\Carbon::parse($item->start_date_request)->diffInHours($item->end_date_request) }} Jam
                        @else
                            {{ \Carbon\Carbon::parse($item->start_date_request)->startOfDay()->diffInDays(\Carbon\Carbon::parse($item->end_date_request)->startOfDay()) + 1 }} Hari
                        @endif
                    </span>
                </div>
            </div>
        </div>

        {{-- Kanan: Harga --}}
        <div class="text-right whitespace-nowrap">
            <p class="text-sm font-medium text-slate-900 dark:text-white">
                Rp {{ number_format($item->sub_total, 0, ',', '.') }}
            </p>
        </div>
    </div>
</div>


@php
    $tariffs = $tariffs ?? [];
    $total = $total ?? 0;
@endphp

@if(count($tariffs) > 0)
    <div class="rounded-lg border border-gray-200 bg-gray-50 p-4">
        <h3 class="mb-3 text-sm font-semibold text-gray-700">Rincian Tarif</h3>
        
        <div class="space-y-2">
            @foreach($tariffs as $tariff)
                <div class="flex items-center justify-between rounded-md bg-white px-3 py-2">
                    <div class="flex-1">
                        <p class="text-sm font-medium text-gray-900">
                            {{ $tariff->incomeType->name }}
                        </p>
                        <p class="text-xs text-gray-500">
                            {{ $tariff->billing_type_label }}
                        </p>
                    </div>
                    <div class="text-right">
                        <p class="text-sm font-semibold text-gray-900">
                            Rp {{ number_format($tariff->amount, 0, ',', '.') }}
                        </p>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-4 border-t border-gray-200 pt-3">
            <div class="flex items-center justify-between">
                <p class="text-sm font-semibold text-gray-700">Total Tagihan</p>
                <p class="text-lg font-bold text-gray-900">
                    Rp {{ number_format($total, 0, ',', '.') }}
                </p>
            </div>
        </div>
    </div>
@else
    <div class="rounded-lg border border-gray-200 bg-gray-50 p-4">
        <p class="text-sm text-gray-500 text-center">
            Pilih jenis pembayaran dan tarif untuk melihat rincian
        </p>
    </div>
@endif


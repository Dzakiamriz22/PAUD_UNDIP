<div class="bg-white border rounded-xl p-6 shadow-sm space-y-6">

    {{-- HEADER --}}
    <div class="flex justify-between items-start border-b pb-4">
        <div>
            <div class="text-lg font-bold">PAUD PERMATA UNDIP</div>
            <div class="text-sm text-gray-500">Kuitansi Pembayaran</div>
        </div>
        <div class="text-right">
            <div class="text-xl font-bold text-green-600">LUNAS</div>
            <div class="text-sm text-gray-500">
                {{ $receipt->receipt_number }}
            </div>
        </div>
    </div>

    {{-- INFO --}}
    <div class="grid grid-cols-2 gap-4 text-sm">
        <div>
            <div class="text-gray-500">Nama Siswa</div>
            <div class="font-semibold">
                {{ $receipt->invoice->student->name ?? '-' }}
            </div>
        </div>
        <div>
            <div class="text-gray-500">Tanggal Pembayaran</div>
            <div class="font-semibold">
                {{ $receipt->payment_date->format('d/m/Y H:i') }}
            </div>
        </div>
    </div>

    {{-- ITEMS --}}
    <div>
        <div class="font-semibold mb-2">Rincian Pembayaran</div>

        <table class="w-full text-sm border">
            <thead class="bg-gray-50">
                <tr>
                    <th class="border px-3 py-2 text-left">Item</th>
                    <th class="border px-3 py-2 text-right">Nominal</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($receipt->invoice->items as $item)
                    @php
                        $isDiscount = $item->tariff?->incomeType?->is_discount ?? false;
                    @endphp
                    <tr class="{{ $isDiscount ? 'text-gray-500 italic' : '' }}">
                        <td class="border px-3 py-2">
                            {{ $item->tariff->incomeType->name ?? '-' }}
                            @if ($item->description)
                                <div class="text-xs text-gray-400">
                                    {{ $item->description }}
                                </div>
                            @endif
                        </td>
                        <td class="border px-3 py-2 text-right">
                            {{ $isDiscount ? '− ' : '' }}
                            Rp {{ number_format(abs($item->final_amount), 0, ',', '.') }}
                        </td>
                    </tr>
                @endforeach
                <tr class="font-bold bg-gray-50">
                    <td class="border px-3 py-2">Total Dibayar</td>
                    <td class="border px-3 py-2 text-right">
                        Rp {{ number_format($receipt->amount_paid, 0, ',', '.') }}
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    {{-- FOOTER --}}
    <div class="flex justify-end text-sm text-gray-500 pt-4 border-t">
        Dibuat oleh {{ $receipt->creator->username ?? 'Bendahara' }}
    </div>

</div>
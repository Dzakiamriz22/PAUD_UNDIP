<div class="bg-white border rounded-xl p-6 shadow-sm space-y-6">

    @php
        $isPaid = !empty($invoice->paid_at) || $invoice->status === 'paid';
        $statusText = $isPaid ? 'LUNAS' : strtoupper($invoice->status ?? 'MENUNGGU');
        $statusClass = $isPaid ? 'text-green-600' : 'text-yellow-600';
    @endphp

    {{-- HEADER --}}
    <div class="flex justify-between items-start border-b pb-4">
        <div>
            <div class="text-lg font-bold">PAUD PERMATA UNDIP</div>
            <div class="text-sm text-gray-500">Invoice Pembayaran</div>
        </div>
        <div class="text-right">
            <div class="text-xl font-bold {{ $statusClass }}">
                {{ $statusText }}
            </div>
            <div class="text-sm text-gray-500">
                {{ $invoice->invoice_number }}
            </div>
        </div>
    </div>

    {{-- INFO --}}
    <div class="grid grid-cols-2 gap-4 text-sm">
        <div>
            <div class="text-gray-500">Nama Siswa</div>
            <div class="font-semibold">
                {{ $invoice->student->name ?? '-' }}
            </div>
        </div>
        <div>
            <div class="text-gray-500">Kelas</div>
            <div class="font-semibold">
                {{ optional(optional($invoice->student)->activeClass)->classRoom->category ?? '-' }}
            </div>
            <div class="text-gray-500 text-xs">
                Tahun Ajaran: {{ $invoice->academicYear->year ?? '-' }}
            </div>
        </div>
    </div>

    <div class="grid grid-cols-2 gap-4 text-sm">
        <div>
            <div class="text-gray-500">Metode Pembayaran</div>
            <div class="font-semibold">
                @if($invoice->va_bank)
                    {{ strtoupper($invoice->va_bank) }} — VA: {{ $invoice->va_number }}
                @else
                    Transfer / Tunai
                @endif
            </div>
        </div>
        <div>
            <div class="text-gray-500">Tanggal Invoice</div>
            <div class="font-semibold">
                {{ $invoice->issued_at?->format('d/m/Y H:i') }}
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
                @foreach ($invoice->items as $item)
                    @php
                        $isDiscount = $item->tariff?->incomeType?->is_discount ?? false;
                        $title = $item->tariff?->incomeType->name
                            ?? $item->description
                            ?? $item->name
                            ?? '-';

                        $showDescription = $item->description
                            && empty($item->tariff);
                    @endphp

                    <tr class="{{ $isDiscount ? 'text-gray-500 italic' : '' }}">
                        <td class="border px-3 py-2">
                            {{ $title }}
                            @if ($showDescription)
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
                    <td class="border px-3 py-2">Total Tagihan</td>
                    <td class="border px-3 py-2 text-right">
                        Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    {{-- FOOTER --}}
    <div class="flex justify-end text-sm text-gray-500 pt-4 border-t">
        Dibuat oleh {{ $invoice->creator->username ?? 'Bendahara' }}
    </div>

</div>
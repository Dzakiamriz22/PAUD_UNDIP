<div class="invoice-doc">
    <div class="flex justify-between items-start mb-6">
        <div>
            <h2 class="text-2xl font-bold">PAUD UNDIP</h2>
            <p class="text-sm text-gray-600">Alamat sekolah (sesuaikan)</p>
        </div>
        <div class="text-right">
            <p class="text-sm">Invoice: <strong>{{ $invoice->invoice_number }}</strong></p>
            <p class="text-sm">Tanggal: {{ $invoice->issued_at?->format('d M Y') }}</p>
            <p class="text-sm">Jatuh tempo: {{ $invoice->due_date?->format('d M Y') ?? '-' }}</p>
        </div>
    </div>

    <div class="mb-4">
        <p><strong>Ditagihkan ke:</strong></p>
        <p>{{ optional($invoice->student)->name ?? '-' }} — {{ optional($invoice->student)->nis ?? '' }}</p>
        <p>Kelas: {{ optional($invoice->class)->name ?? '-' }} | Tahun Ajaran: {{ optional($invoice->academicYear)->name ?? '-' }}</p>
    </div>

    <div class="overflow-x-auto mb-4">
        <table class="min-w-full border-collapse border">
            <thead>
                <tr class="bg-gray-100">
                    <th class="border px-3 py-2 text-left">No</th>
                    <th class="border px-3 py-2 text-left">Deskripsi</th>
                    <th class="border px-3 py-2 text-right">Jumlah</th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoice->items as $i => $item)
                    <tr>
                        <td class="border px-3 py-2">{{ $i + 1 }}</td>
                        <td class="border px-3 py-2">{{ $item->description ?? $item->name ?? '-' }}</td>
                        <td class="border px-3 py-2 text-right">Rp {{ number_format($item->final_amount, 0, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="2" class="border px-3 py-2 text-right font-semibold">Total</td>
                    <td class="border px-3 py-2 text-right font-semibold">Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}</td>
                </tr>
            </tfoot>
        </table>
    </div>

    <div class="text-sm text-gray-700">
        <p>Metode pembayaran: {{ $invoice->va_bank ? strtoupper($invoice->va_bank).' (VA: '.$invoice->va_number.')' : 'Transfer / Tunai' }}</p>
        <p class="mt-2">Terima kasih atas pembayaran Anda.</p>
    </div>
</div>

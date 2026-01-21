<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Bukti Pembayaran (Batch)</title>
    @include('partials.payment-styles')
</head>
<body class="payment-doc">

@foreach ($invoices as $invoice)

    @php
        $isPaid = !empty($invoice->paid_at) || $invoice->status === 'paid';
        $statusText = $isPaid ? 'LUNAS' : 'BELUM LUNAS';
        $statusClass = $isPaid ? 'status-paid' : 'status-unpaid';
    @endphp

    <div class="page">
    @include('partials.payment-header', [
        'title' => 'INVOICE',
    ])

    <table class="info-table">
        <tr>
            <td width="20%">No Transaksi</td>
            <td width="30%">: {{ $invoice->invoice_number }}</td>
            <td width="20%">Tanggal</td>
            <td width="30%">: {{ $invoice->issued_at?->format('d/m/Y') }}</td>
        </tr>
        <tr>
            <td>No Induk</td>
            <td>: {{ optional($invoice->student)->nis ?? '-' }}</td>
            <td>Waktu</td>
            <td>: {{ $invoice->issued_at?->format('H:i') }}</td>
        </tr>
        <tr>
            <td>Nama Siswa</td>
            <td>: {{ optional($invoice->student)->name ?? '-' }}</td>
            <td>Kelas</td>
            <td>: {{ optional(optional($invoice->student)->activeClass)->classRoom->category ?? '-' }}</td>
        </tr>
        <tr>
            <td>Tahun Ajaran</td>
            <td colspan="3">: {{ optional($invoice->academicYear)->year ?? '-' }}</td>
        </tr>
        <tr>
            <td>Jatuh Tempo</td>
            <td colspan="3">: {{ $invoice->due_date?->format('d/m/Y') ?? '-' }}</td>
        </tr>
        <tr>
            <td>Metode Pembayaran</td>
            <td colspan="3">
                @if($invoice->va_bank)
                    : {{ strtoupper($invoice->va_bank) }} — VA: {{ $invoice->va_number }}
                @else
                    : Transfer / Tunai
                @endif
            </td>
        </tr>
    </table>

    <table class="payment">
        <thead>
            <tr>
                <th>No</th>
                <th>Rincian Pembayaran</th>
                <th width="30%" class="amount">Nominal</th>
            </tr>
        </thead>
        <tbody>
            @php
                $subtotal = 0;
                $totalDiscount = 0;
            @endphp
            @foreach($invoice->items as $item)
                @php
                    $isDiscount = $item->tariff?->incomeType?->is_discount ?? false;
                    $amount = $item->amount ?? $item->final_amount ?? 0;
                    if ($isDiscount) {
                        $totalDiscount += abs($amount);
                    } else {
                        $subtotal += $amount;
                    }
                @endphp
                <tr class="{{ $isDiscount ? 'discount' : '' }}">
                    <td width="40">{{ $loop->iteration }}</td>
                    <td>
                        {{ strtoupper($item->description ?? $item->name ?? '-') }}
                    </td>
                    <td class="amount">{{ $isDiscount ? '− ' : '' }}Rp {{ number_format(abs($amount), 0, ',', '.') }}</td>
                </tr>
            @endforeach

            <tr class="small">
                <td colspan="2">Subtotal</td>
                <td class="amount">Rp {{ number_format($subtotal, 0, ',', '.') }}</td>
            </tr>

            @if($totalDiscount > 0)
                <tr class="small">
                    <td colspan="2">Total Diskon</td>
                    <td class="amount">− Rp {{ number_format($totalDiscount, 0, ',', '.') }}</td>
                </tr>
            @endif

            <tr class="total-row">
                <th colspan="2">Total Tagihan</th>
                <th class="amount">Rp {{ number_format(max(0, $subtotal - $totalDiscount), 0, ',', '.') }}</th>
            </tr>
        </tbody>
    </table>

    @include('partials.payment-footer', [
        'signatureDate' => now()->format('d F Y'),
        'signatureName' => auth()->user()->name ?? 'Admin',
        'signatureRole' => 'Petugas / Bendahara',
    ])

    </div>

    {{-- PAGE BREAK kecuali invoice terakhir --}}
    @if (! $loop->last)
        <div class="page-break"></div>
    @endif

@endforeach

</body>
</html>
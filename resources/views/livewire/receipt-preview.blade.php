@include('partials.payment-styles')

<div class="payment-doc">

    @php
        $statusText = 'LUNAS';
        $statusClass = 'status-paid';
    @endphp

    @include('partials.payment-header', [
        'title' => 'KUITANSI',
        'statusText' => $statusText,
        'statusClass' => $statusClass,
    ])

    <table class="info-table">
        <tr>
            <td width="20%">Nomor Kuitansi</td>
            <td width="30%">: {{ $receipt->receipt_number }}</td>
            <td width="20%">Tanggal</td>
            <td width="30%">: {{ $receipt->payment_date->format('d/m/Y') }}</td>
        </tr>
        <tr>
            <td>Nama Siswa</td>
            <td>: {{ $receipt->invoice->student->name ?? '-' }}</td>
            <td>Waktu</td>
            <td>: {{ $receipt->payment_date->format('H:i') }}</td>
        </tr>
        <tr>
            <td>Kelas</td>
            <td>: {{ optional(optional($receipt->invoice->student)->activeClass)->classRoom->category ?? '-' }}</td>
            <td>Tahun Ajaran</td>
            <td>: {{ optional($receipt->invoice->academicYear)->year ?? '-' }}</td>
        </tr>
        <tr>
            <td>Metode Pembayaran</td>
            <td colspan="3">
                @if($receipt->invoice->va_bank)
                    {{ strtoupper($receipt->invoice->va_bank) }} — VA: {{ $receipt->invoice->va_number }}
                @else
                    Transfer / Tunai
                @endif
            </td>
        </tr>
    </table>

    <table class="payment">
        <thead>
            <tr>
                <th>Rincian Pembayaran</th>
                <th width="30%" class="amount">Nominal</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($receipt->invoice->items as $item)
                @php $isDiscount = $item->tariff?->incomeType?->is_discount ?? false; @endphp
                <tr class="{{ $isDiscount ? 'discount' : '' }}">
                    @php
                        $title = $item->tariff?->incomeType->name ?? $item->description ?? $item->name ?? '-';
                        $showDescription = $item->description && (
                            empty($item->tariff) ||
                            (($item->tariff?->incomeType?->name ?? '') !== ($item->description ?? ''))
                        );
                    @endphp
                    <td>
                        {{ $title }}
                        @if ($showDescription)
                            <br><small>{{ $item->description }}</small>
                        @endif
                    </td>
                    <td class="amount">
                        {{ $isDiscount ? '− ' : '' }}
                        Rp {{ number_format(abs($item->final_amount), 0, ',', '.') }}
                    </td>
                </tr>
            @endforeach

            <tr class="total-row">
                <th>Total Dibayar</th>
                <th class="amount">Rp {{ number_format($receipt->amount_paid, 0, ',', '.') }}</th>
            </tr>
        </tbody>
    </table>

    @include('partials.payment-footer', [
        'signatureDate' => now()->format('d F Y'),
        'signatureName' => $receipt->creator->username ?? 'Bendahara',
        'signatureRole' => 'Bendahara',
    ])

</div>
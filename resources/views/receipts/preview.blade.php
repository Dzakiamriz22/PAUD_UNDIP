<div style="border:1px solid #d1d5db; padding:24px; background:#fff">

    <div style="display:flex; justify-content:space-between; margin-bottom:20px">
        <div>
            <strong>PAUD PERMATA UNDIP</strong><br>
            Jl. Pendidikan No. 1
        </div>
        <div style="text-align:right">
            <h3 style="margin:0">KUITANSI</h3>
            <span style="color:#16a34a; font-weight:bold">LUNAS</span>
        </div>
    </div>

    <table width="100%" style="margin-bottom:16px">
        <tr>
            <td width="20%">Nomor</td>
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
    </table>

    <table width="100%" border="1" cellspacing="0" cellpadding="8">
        <thead style="background:#f3f4f6">
            <tr>
                <th align="left">Rincian Pembayaran</th>
                <th align="right">Nominal</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($receipt->invoice->items as $item)
                @php
                    $isDiscount = $item->tariff?->incomeType?->is_discount ?? false;
                @endphp
                <tr style="{{ $isDiscount ? 'color:#6b7280;font-style:italic' : '' }}">
                    <td>
                        {{ $item->tariff->incomeType->name ?? '-' }}
                        @if ($item->description)
                            <br><small>{{ $item->description }}</small>
                        @endif
                    </td>
                    <td align="right">
                        {{ $isDiscount ? '− ' : '' }}
                        Rp {{ number_format(abs($item->final_amount), 0, ',', '.') }}
                    </td>
                </tr>
            @endforeach
            <tr style="font-weight:bold;background:#f9fafb">
                <td>Total Dibayar</td>
                <td align="right">
                    Rp {{ number_format($receipt->amount_paid, 0, ',', '.') }}
                </td>
            </tr>
        </tbody>
    </table>

</div>
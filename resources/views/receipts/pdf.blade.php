<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kuitansi Pembayaran</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            color: #111827;
        }

        /* ===== HEADER ===== */
        .header {
            border-bottom: 2px solid #111827;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .school-name {
            font-size: 14px;
            font-weight: bold;
        }
        .doc-title {
            text-align: right;
        }
        .doc-title h2 {
            margin: 0;
        }
        .status {
            font-size: 11px;
            font-weight: bold;
            color: #16a34a;
        }

        /* ===== INFO TABLE ===== */
        .info-table {
            width: 100%;
            margin-bottom: 20px;
        }
        .info-table td {
            padding: 4px 0;
        }

        /* ===== PAYMENT TABLE ===== */
        table.payment {
            width: 100%;
            border-collapse: collapse;
        }
        table.payment th,
        table.payment td {
            border: 1px solid #d1d5db;
            padding: 8px;
        }
        table.payment th {
            background: #f3f4f6;
            text-align: left;
        }
        table.payment td.amount {
            text-align: right;
        }

        /* ===== DISCOUNT ===== */
        .discount {
            color: #6b7280;
            font-style: italic;
        }

        /* ===== TOTAL ===== */
        .total-row th,
        .total-row td {
            font-weight: bold;
            background: #f9fafb;
        }

        /* ===== FOOTER ===== */
        .footer {
            margin-top: 40px;
            width: 100%;
        }
        .signature {
            width: 40%;
            float: right;
            text-align: center;
        }
        .signature .name {
            margin-top: 60px;
            font-weight: bold;
        }
        .clear {
            clear: both;
        }
    </style>
</head>
<body>

{{-- ================= HEADER ================= --}}
<div class="header">
    <table width="100%">
        <tr>
            <td>
                <div class="school-name">PAUD PERMATA UNDIP</div>
                <div>Jl. Pendidikan No. 1</div>
            </td>
            <td class="doc-title">
                <h2>KUITANSI</h2>
                <div class="status">LUNAS</div>
            </td>
        </tr>
    </table>
</div>

{{-- ================= INFO ================= --}}
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
</table>

{{-- ================= ITEMS ================= --}}
<table class="payment">
    <thead>
        <tr>
            <th>Rincian Pembayaran</th>
            <th width="30%" class="amount">Nominal</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($receipt->invoice->items as $item)
            @php
                $isDiscount = $item->tariff?->incomeType?->is_discount ?? false;
            @endphp
            <tr class="{{ $isDiscount ? 'discount' : '' }}">
                <td>
                    {{ $item->tariff->incomeType->name ?? '-' }}
                    @if ($item->description)
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
            <th class="amount">
                Rp {{ number_format($receipt->amount_paid, 0, ',', '.') }}
            </th>
        </tr>
    </tbody>
</table>

{{-- ================= FOOTER ================= --}}
<div class="footer">
    <div class="signature">
        <div>{{ now()->format('d F Y') }}</div>
        <div class="name">
            {{ $receipt->creator->username ?? 'Bendahara' }}
        </div>
        <div>Bendahara</div>
    </div>
    <div class="clear"></div>
</div>

</body>
</html>
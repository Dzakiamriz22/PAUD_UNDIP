<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Bukti Pembayaran - {{ $invoice->invoice_number }}</title>
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
            font-size: 18px;
        }
        .status {
            font-size: 11px;
            font-weight: bold;
            color: #dc2626;          /* Merah untuk BELUM LUNAS */
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

        .center { text-align: center; }
    </style>
</head>
<body>

{{-- ================= HEADER ================= --}}
<div class="header">
    <table width="100%">
        <tr>
            <td>
                @if(file_exists(public_path('images/logo.png')))
                    <img src="{{ public_path('images/logo.png') }}" alt="logo" style="width:60px; height:auto; margin-bottom:4px;" />
                @endif
                <div class="school-name">{{ strtoupper(config('app.name')) }}</div>
                <div>Alamat Sekolah</div>
            </td>
            <td class="doc-title">
                <h2>BUKTI PEMBAYARAN</h2>
                <div class="status">BELUM LUNAS</div>
            </td>
        </tr>
    </table>
</div>

{{-- ================= INFO ================= --}}
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
</table>

{{-- ================= ITEMS ================= --}}
<table class="payment">
    <thead>
        <tr>
            <th>No</th>
            <th>Rincian Pembayaran</th>
            <th width="30%" class="amount">Nominal</th>
        </tr>
    </thead>
    <tbody>
        @foreach($invoice->items as $item)
        <tr>
            <td width="40">{{ $loop->iteration }}</td>
            <td>
                {{ strtoupper($item->description ?? $item->name ?? '-') }}
            </td>
            <td class="amount">
                Rp {{ number_format($item->amount ?? $item->final_amount ?? 0, 0, ',', '.') }}
            </td>
        </tr>
        @endforeach

        <tr class="total-row">
            <th colspan="2">Total Tagihan</th>
            <th class="amount">
                Rp {{ number_format($invoice->total_amount ?? $invoice->items->sum(fn($i) => $i->amount ?? $i->final_amount ?? 0), 0, ',', '.') }}
            </th>
        </tr>
    </tbody>
</table>

{{-- ================= FOOTER ================= --}}
<div class="footer">
    <div class="signature">
        <div>Indonesia, {{ now()->format('d F Y') }}</div>
        <div class="name">
            {{ auth()->user()->name ?? 'Admin' }}
        </div>
        <div>Petugas / Bendahara</div>
    </div>
    <div class="clear"></div>
</div>

</body>
</html>
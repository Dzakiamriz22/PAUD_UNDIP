<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Preview Invoice - {{ $invoice->invoice_number }}</title>
    <style>
        body { font-family: DejaVu Sans, monospace; font-size: 13px; color: #000; margin:20px; }
        .container { max-width: 900px; margin: 0 auto; }
        .actions { display:flex; justify-content:flex-end; gap:8px; margin-bottom:12px; }
        .btn { display:inline-block; padding:8px 12px; border-radius:4px; text-decoration:none; color:#fff; }
        .btn-primary { background:#1f93ff; }
        .btn-secondary { background:#6b7280; }
        .wrap { width: 700px; margin: 0 auto; }
        .header { text-align: center; }
        .header .title { font-weight: bold; letter-spacing: 1px; }
        .logo { position: absolute; left: 40px; top: 20px; }
        .meta { width: 100%; margin-top: 6px; }
        .meta td { vertical-align: top; padding: 2px 6px; }
        .sep { border-top: 1px dashed #000; margin: 8px 0; }
        .items { width: 100%; font-family: monospace; }
        .items td { padding: 2px 6px; }
        .items .desc { padding-left: 6px; }
        .right { text-align: right; }
        .totals { width: 100%; margin-top: 8px; }
        .totals td { padding: 2px 6px; }
        .center { text-align: center; }
    </style>
</head>
<body>

<div class="container">
    <div class="actions">
        <a href="{{ route('invoices.download', $invoice) }}" class="btn btn-primary">Download PDF</a>
        <a href="javascript:window.print()" class="btn btn-secondary">Print</a>
    </div>

    <div class="wrap">
        @php
            $logoFile = null;
            if (file_exists(public_path('images/LOGO-PAUD-PERMATA.png'))) {
                $logoFile = asset('images/LOGO-PAUD-PERMATA.png');
            } elseif (file_exists(public_path('images/logo.png'))) {
                $logoFile = asset('images/logo.png');
            }
        @endphp

        @if($logoFile)
            <div class="logo">
                <img src="{{ $logoFile }}" alt="logo" style="width:60px; height:auto;" />
            </div>
        @endif

        <div class="header">
            <div class="small">&nbsp;</div>
            <div class="title">{{ strtoupper(config('app.name')) }}</div>
            <div class="small">{{ config('app.address', 'Alamat Sekolah') }}</div>
        </div>

        <div class="sep"></div>

        <table class="meta">
            <tr>
                <td style="width:60%;">
                    No Transaksi : {{ $invoice->invoice_number }}<br>
                    No Induk      : {{ optional($invoice->student)->nis ?? '-' }}<br>
                    Nama          : {{ optional($invoice->student)->name ?? '-' }}
                </td>
                <td style="width:40%; text-align:right;">
                    Tanggal : {{ $invoice->issued_at?->format('d-m-Y H:i:s') }}<br>
                    Kelas   : {{ optional(optional($invoice->student)->activeClass)->classRoom->name ?? '-' }}
                </td>
            </tr>
        </table>

        <div class="sep"></div>

        <table class="items">
            <thead>
                <tr>
                    <td style="width:40px;">No</td>
                    <td style="width:520px;">Nama Pembayaran</td>
                    <td style="width:120px;" class="right">Nominal</td>
                </tr>
            </thead>
            <tbody>
                @foreach($invoice->items as $item)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td class="desc">{{ strtoupper($item->description ?? $item->name ?? '-') }}</td>
                    <td class="right">{{ number_format($item->amount ?? $item->final_amount ?? 0,0,',','.') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="sep"></div>

        <table class="totals">
            <tr>
                <td style="width:70%;"></td>
                <td style="width:30%;">
                    <table style="width:100%; font-family: monospace;">
                        <tr>
                            <td style="width:60%;">Total :</td>
                            <td class="right">{{ number_format($invoice->total_amount ?? $invoice->items->sum(fn($i)=> $i->amount ?? $i->final_amount ?? 0),0,',','.') }}</td>
                        </tr>
                        <tr>
                            <td>Tunai :</td>
                            <td class="right">{{ number_format($invoice->total_amount ?? 0,0,',','.') }}</td>
                        </tr>
                        <tr>
                            <td>Kembali :</td>
                            <td class="right">0</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        <div class="sep"></div>

        <div style="margin-top:8px;">
            <div class="center">Indonesia, {{ $invoice->issued_at?->format('d-m-Y') }}</div>
            <div style="height:40px;"></div>
            <div class="center">Petugas</div>
            <div style="height:30px;"></div>
            <div class="center">{{ auth()->user()->name ?? 'Admin' }}</div>
        </div>

    </div>
</div>

</body>
</html>

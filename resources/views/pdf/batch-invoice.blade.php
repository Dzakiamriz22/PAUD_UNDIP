<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Batch Invoice</title>
    <style>
        body {
            font-family: DejaVu Sans, monospace;
            font-size: 12px;
            color: #000;
        }
        .wrap {
            width: 700px;
            margin: 0 auto;
        }
        .invoice {
            page-break-after: always;
            position: relative;
        }
        .header {
            text-align: center;
        }
        .title {
            font-weight: bold;
            letter-spacing: 1px;
        }
        .logo {
            position: absolute;
            left: 40px;
            top: 20px;
        }
        .meta {
            width: 100%;
            margin-top: 6px;
        }
        .meta td {
            vertical-align: top;
            padding: 2px 6px;
        }
        .sep {
            border-top: 1px dashed #000;
            margin: 8px 0;
        }
        .items {
            width: 100%;
            font-family: monospace;
        }
        .items td {
            padding: 2px 6px;
        }
        .items .desc {
            padding-left: 6px;
        }
        .right {
            text-align: right;
        }
        .totals {
            width: 100%;
            margin-top: 8px;
        }
        .totals td {
            padding: 2px 6px;
        }
        .center {
            text-align: center;
        }
    </style>
</head>
<body>

@foreach ($invoices as $invoice)
<div class="invoice">
    <div class="wrap">

        {{-- LOGO --}}
        @if(file_exists(public_path('images/logo.png')))
            <div class="logo">
                <img src="{{ public_path('images/logo.png') }}"
                     style="width:60px;height:auto;">
            </div>
        @endif

        {{-- HEADER --}}
        <div class="header">
            <div class="title">{{ strtoupper(config('app.name')) }}</div>
            <div>INVOICE PEMBAYARAN</div>
        </div>

        <div class="sep"></div>

        {{-- META --}}
        <table class="meta">
            <tr>
                <td style="width:60%;">
                    No Invoice : {{ $invoice->invoice_number }}<br>
                    NIS        : {{ $invoice->student->nis ?? '-' }}<br>
                    Nama       : {{ $invoice->student->name ?? '-' }}
                </td>
                <td style="width:40%; text-align:right;">
                    Tanggal : {{ $invoice->issued_at?->format('d-m-Y') }}<br>
                    Kelas   : {{ optional(optional($invoice->student)->activeClass)->classRoom->name ?? '-' }}
                </td>
            </tr>
        </table>

        <div class="sep"></div>

        {{-- ITEMS --}}
        <table class="items">
            <thead>
                <tr>
                    <td style="width:40px;">No</td>
                    <td style="width:520px;">Nama Pembayaran</td>
                    <td style="width:120px;" class="right">Nominal</td>
                </tr>
            </thead>
            <tbody>
                @foreach ($invoice->items as $item)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td class="desc">{{ strtoupper($item->description ?? '-') }}</td>
                    <td class="right">
                        {{ number_format($item->final_amount ?? 0,0,',','.') }}
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="sep"></div>

        {{-- TOTAL --}}
        <table class="totals">
            <tr>
                <td style="width:70%;"></td>
                <td style="width:30%;">
                    <table style="width:100%; font-family: monospace;">
                        <tr>
                            <td>Total :</td>
                            <td class="right">
                                {{ number_format($invoice->total_amount,0,',','.') }}
                            </td>
                        </tr>
                        <tr>
                            <td>Tunai :</td>
                            <td class="right">
                                {{ number_format($invoice->total_amount,0,',','.') }}
                            </td>
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

        {{-- FOOTER --}}
        <div style="margin-top:8px;">
            <div class="center">
                Indonesia, {{ $invoice->issued_at?->format('d-m-Y') }}
            </div>
            <div style="height:40px;"></div>
            <div class="center">Petugas</div>
            <div style="height:30px;"></div>
            <div class="center">{{ auth()->user()->name ?? 'Admin' }}</div>
        </div>

    </div>
</div>
@endforeach

</body>
</html>

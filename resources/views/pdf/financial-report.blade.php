<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Keuangan - PAUD UNDIP</title>
    @include('partials.payment-styles')
    <style>
        /* Override untuk landscape dan financial report styling */
        @page { size: A4 landscape; margin: 10mm; }
        body.payment-doc { font-size: 9px; line-height: 1.3; }
        
        /* Header Customization */
        .report-header { text-align: center; margin-bottom: 10px; padding-bottom: 8px; border-bottom: 2px solid #1e40af; }
        .report-header h1 { font-size: 16px; color: #1e40af; margin: 0 0 2px 0; font-weight: 700; }
        .report-header h2 { font-size: 10px; color: #6b7280; font-weight: 400; margin: 0 0 5px 0; }
        .payment-badge { display: inline-block; background: #dbeafe; padding: 2px 8px; border-radius: 3px; color: #1e40af; font-weight: 600; font-size: 8px; margin: 4px 0; }
        .report-meta { font-size: 8px; color: #6b7280; margin-top: 5px; }
        
        /* Summary Grid - 4 columns */
        .summary-grid { display: table; width: 100%; margin: 8px 0; border-collapse: separate; border-spacing: 3px; }
        .summary-cell { display: table-cell; padding: 6px; border: 1px solid #e5e7eb; background: #f9fafb; width: 25%; vertical-align: top; }
        .summary-cell .label { font-size: 7px; color: #6b7280; text-transform: uppercase; margin-bottom: 2px; font-weight: 600; display: block; }
        .summary-cell .value { font-size: 11px; font-weight: 700; color: #111827; display: block; margin-bottom: 2px; }
        .summary-cell .sub { font-size: 7px; color: #6b7280; display: block; }
        
        /* Two Column Layout */
        .two-col { width: 100%; margin-bottom: 8px; }
        .two-col:after { content: ""; display: table; clear: both; }
        .col { width: 49%; float: left; }
        .col:first-child { margin-right: 2%; }
        
        /* Section Styling */
        .section { margin-bottom: 8px; }
        .section-title { font-size: 9px; font-weight: 700; color: #1e40af; margin-bottom: 4px; padding-bottom: 2px; border-bottom: 1px solid #cbd5e1; }
        
        /* Table Overrides for Financial Report */
        table.compact { font-size: 8px; }
        table.compact th { padding: 4px; font-size: 8px; background: #f1f5f9; }
        table.compact td { padding: 4px; font-size: 8px; }
        table.compact tr:nth-child(even) { background: #f9fafb; }
        
        /* Badges */
        .badge-sm { display: inline-block; padding: 1px 4px; background: #dbeafe; color: #1e40af; font-weight: 600; font-size: 7px; border-radius: 2px; }
        
        /* Rank Badges */
        .rank-badge { display: inline-block; width: 14px; height: 14px; line-height: 14px; text-align: center; border-radius: 50%; font-weight: 700; color: white; font-size: 7px; margin-right: 3px; }
        .rank-1 { background: #eab308; }
        .rank-2 { background: #9ca3af; }
        .rank-3 { background: #f97316; }
        .rank-other { background: #3b82f6; }
        
        /* Footer */
        .report-footer { margin-top: 12px; padding-top: 8px; border-top: 1px solid #cbd5e1; text-align: center; font-size: 7px; color: #6b7280; }
        .report-footer strong { color: #111827; }
        
        /* Mini stats boxes */
        .mini-stat { border: 1px solid #e5e7eb; padding: 4px 6px; background: #fafafa; margin-bottom: 3px; }
        .mini-stat .label { font-size: 7px; color: #6b7280; }
        .mini-stat .value { font-size: 9px; font-weight: 700; color: #111827; }
        
        .text-red { color: #dc2626; }
        .text-green { color: #16a34a; }
    </style>
</head>
<body class="payment-doc">

<div class="report-header">
    <table width="100%">
        <tr>
            <td width="20%" style="text-align: left; vertical-align: middle;">
                @php
                    $logoFile = public_path('images/LOGO-PAUD-PERMATA.png');
                    if (!file_exists($logoFile)) {
                        $logoFile = public_path('images/logo.png');
                    }
                @endphp
                @if(file_exists($logoFile))
                    <img src="{{ $logoFile }}" alt="logo" style="width:50px; height:auto;" />
                @endif
            </td>
            <td width="60%" style="text-align: center; vertical-align: middle;">
                <h1>LAPORAN KEUANGAN</h1>
                <h2>{{ strtoupper(config('app.name', 'PAUD UNDIP')) }}</h2>
                <div class="payment-badge">Sistem Pembayaran: Virtual Account BNI</div>
                <div class="report-meta">
                    <strong>Periode:</strong> 
                    @if($granularity === 'monthly')
                        {{ \DateTime::createFromFormat('!m', $month)->format('F') }} {{ $year }}
                    @else
                        Tahun {{ $year }}
                    @endif
                    | <strong>Dicetak:</strong> {{ $generatedAt->format('d F Y, H:i') }} WIB
                </div>
            </td>
            <td width="20%"></td>
        </tr>
    </table>
</div>

<!-- Summary Stats -->
<div class="summary-grid">
    <div class="summary-cell">
        <span class="label">Total Pembayaran</span>
        <span class="value">Rp {{ number_format($currentPeriodTotal, 0, ',', '.') }}</span>
        <span class="sub {{ $periodChangePercent >= 0 ? 'text-green' : 'text-red' }}">
            {{ $periodChangePercent >= 0 ? '↗' : '↘' }} {{ number_format(abs($periodChangePercent), 1) }}% vs periode lalu
        </span>
    </div>
    <div class="summary-cell">
        <span class="label">Total Tagihan</span>
        <span class="value">Rp {{ number_format($totalInvoiced, 0, ',', '.') }}</span>
        <span class="sub">Tingkat Koleksi: {{ number_format($collectionRate, 1) }}%</span>
    </div>
    <div class="summary-cell">
        <span class="label">Tunggakan</span>
        <span class="value text-red">Rp {{ number_format($totalOutstanding, 0, ',', '.') }}</span>
        <span class="sub">{{ $totalInvoiced > 0 ? number_format(($totalOutstanding / $totalInvoiced) * 100, 1) : 0 }}% dari total tagihan</span>
    </div>
    <div class="summary-cell">
        <span class="label">Total Diskon</span>
        <span class="value">Rp {{ number_format($totalDiscounts, 0, ',', '.') }}</span>
        <span class="sub">Potongan harga yang diberikan</span>
    </div>
</div>

<!-- Secondary Stats -->
<div style="margin-bottom: 8px;">
    <table width="100%" cellpadding="0" cellspacing="3">
        <tr>
            <td width="33%" valign="top">
                <div class="mini-stat">
                    <div class="label">Rata-rata Transaksi</div>
                    <div class="value">Rp {{ number_format($averageTransactionValue, 0, ',', '.') }}</div>
                </div>
            </td>
            <td width="33%" valign="top">
                <div class="mini-stat">
                    <div class="label">Total Transaksi</div>
                    <div class="value">{{ number_format($transactionCount, 0, ',', '.') }}</div>
                </div>
            </td>
            <td width="33%" valign="top">
                <div class="mini-stat">
                    <div class="label">Periode Sebelumnya</div>
                    <div class="value">Rp {{ number_format($previousPeriodTotal, 0, ',', '.') }}</div>
                </div>
            </td>
        </tr>
    </table>
</div>

<!-- Two Column Layout -->
<div class="two-col">
    <div class="col">
        <div class="section">
            <div class="section-title">Laporan Agregat</div>
            <table class="compact" width="100%">
                <thead>
                    <tr>
                        <th>Periode</th>
                        <th style="text-align: center;">Transaksi</th>
                        <th style="text-align: right;">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($reportRows as $row)
                        <tr>
                            <td>
                                @if($row['month'])
                                    {{ \DateTime::createFromFormat('!m', $row['month'])->format('M') }} {{ $row['year'] }}
                                @else
                                    {{ $row['year'] }}
                                @endif
                            </td>
                            <td style="text-align: center;"><span class="badge-sm">{{ $row['count'] }}</span></td>
                            <td style="text-align: right;"><strong>Rp {{ number_format($row['total_amount'], 0, ',', '.') }}</strong></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" style="text-align: center; padding: 8px; color: #6b7280;">Tidak ada data</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="col">
        <div class="section">
            <div class="section-title">Sumber Pemasukan</div>
            <table class="compact" width="100%">
                <thead>
                    <tr>
                        <th>Sumber</th>
                        <th style="text-align: center;">Item</th>
                        <th style="text-align: right;">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @php $grandTotal = array_sum(array_column($incomeSources, 'total_amount') ?: [0]); @endphp
                    @forelse($incomeSources as $source)
                        @php $percentage = $grandTotal > 0 ? ($source['total_amount'] / $grandTotal) * 100 : 0; @endphp
                        <tr>
                            <td>
                                <strong>{{ $source['income_type'] }}</strong><br>
                                <span style="font-size: 7px; color: #6b7280;">{{ number_format($percentage, 1) }}%</span>
                            </td>
                            <td style="text-align: center;"><span class="badge-sm">{{ $source['items_count'] }}</span></td>
                            <td style="text-align: right;">Rp {{ number_format($source['total_amount'], 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" style="text-align: center; padding: 8px; color: #6b7280;">Tidak ada data</td>
                        </tr>
                    @endforelse
                </tbody>
                @if(count($incomeSources) > 0)
                <tfoot style="background: #e5e7eb;">
                    <tr>
                        <th>TOTAL</th>
                        <th style="text-align: center;">{{ array_sum(array_column($incomeSources, 'items_count')) }}</th>
                        <th style="text-align: right;">Rp {{ number_format($grandTotal, 0, ',', '.') }}</th>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>
</div>

<!-- Collection by Class -->
@if(!empty($collectionByClass) && count($collectionByClass) > 0)
<div class="section">
    <div class="section-title">Ringkasan Koleksi per Kelas</div>
    <table class="compact" width="100%">
        <thead>
            <tr>
                <th>Kelas</th>
                <th style="width: 60px; text-align: center;">Siswa</th>
                <th style="width: 100px; text-align: right;">Total Tagihan</th>
                <th style="width: 100px; text-align: right;">Pembayaran</th>
                <th style="width: 80px; text-align: right;">Tunggakan</th>
                <th style="width: 80px; text-align: center;">Koleksi %</th>
            </tr>
        </thead>
        <tbody>
            @foreach($collectionByClass as $class)
                <tr>
                    <td><strong>{{ $class['class_name'] }}</strong></td>
                    <td style="text-align: center;">{{ $class['student_count'] }}</td>
                    <td style="text-align: right;">Rp {{ number_format($class['total_invoiced'], 0, ',', '.') }}</td>
                    <td style="text-align: right;"><strong>Rp {{ number_format($class['total_paid'], 0, ',', '.') }}</strong></td>
                    <td style="text-align: right;">Rp {{ number_format($class['outstanding'], 0, ',', '.') }}</td>
                    <td style="text-align: center; font-weight: 700;">
                        <span class="badge-sm" style="
                            @switch(true)
                                @case($class['collection_rate'] >= 85)
                                    background: #dcfce7; color: #166534;
                                    @break
                                @case($class['collection_rate'] >= 70)
                                    background: #fef3c7; color: #92400e;
                                    @break
                                @default
                                    background: #fee2e2; color: #991b1b;
                            @endswitch
                        ">
                            {{ number_format($class['collection_rate'], 1) }}%
                        </span>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

<!-- Footer -->
<div class="report-footer">
    <p><strong>{{ strtoupper(config('app.name', 'PAUD UNDIP')) }}</strong> - Sistem Informasi Keuangan</p>
    <p style="margin-top: 3px;">Laporan dihasilkan secara otomatis pada {{ $generatedAt->format('d F Y, H:i:s') }} WIB</p>
    <p style="margin-top: 2px;">Pembayaran melalui Virtual Account BNI | Untuk informasi lebih lanjut hubungi bagian keuangan</p>
</div>

</body>
</html>

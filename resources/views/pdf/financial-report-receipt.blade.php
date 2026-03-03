<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Penerimaan</title>
    @include('partials.payment-styles')
    <style>
        @page { size: A4 landscape; margin: 10mm; }
        body { font-size: 9.5px; line-height: 1.35; color: #000; }
        table { width: 100%; border-collapse: collapse; }
        .header-table { margin-bottom: 6px; }
        .header-table td { vertical-align: middle; }
        .title { font-size: 12px; font-weight: 700; text-align: center; letter-spacing: 0.2px; }
        .subtitle { font-size: 9px; font-weight: 600; text-align: center; }
        .logo { width: 60px; }
        .filter-table { border: none; border-collapse: collapse; }
        .filter-table td { padding: 3px 5px; border: none; }
        .label { font-weight: 700; width: 120px; }
        .separator { width: 10px; text-align: center; }
        .data-table th,
        .data-table td { border: 1px solid #000; padding: 4px; }
        .data-table th { background: #f0f0f0; text-align: center; font-weight: 700; }
        .data-table td { vertical-align: top; }
        .total-row td { font-weight: 700; background: #f0f0f0; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .desc-list { margin: 0; padding-left: 14px; }
        .desc-list li { margin: 0 0 2px 0; }
        .signature { margin-top: 18px; }
        .signature td { padding: 2px 4px; }
        .signature .title { font-size: 9px; font-weight: 700; text-align: left; }
        .signature .name { padding-top: 24px; font-weight: 700; }
    </style>
</head>
<body>
@php
    $logoFile = public_path('images/LOGO-PAUD-PERMATA.png');
    if (!file_exists($logoFile)) {
        $logoFile = public_path('images/logo.png');
    }
@endphp

<div class="report-block">
    <table class="header-table">
        <tr>
            <td class="logo" style="text-align: left;">
                @if(file_exists($logoFile))
                    <img src="{{ $logoFile }}" alt="logo" style="width:50px; height:auto;" />
                @endif
            </td>
            <td>
                <div class="title">LAPORAN PENERIMAAN (RECEIPT REPORT)</div>
                <div class="subtitle">KEGIATAN USAHA BISNIS DAN KOMERSIAL UNIVERSITAS DIPONEGORO</div>
                <div class="subtitle">PADA UPKAB BP UBIKAR</div>
            </td>
            <td class="logo"></td>
        </tr>
    </table>

    <table class="filter-table" style="margin-top: 6px;">
        <tr>
            <td class="label">Unit Usaha</td>
            <td class="separator">:</td>
            <td>{{ $filters['unit_usaha'] ?? '-' }}</td>
            <td class="label">Status</td>
            <td class="separator">:</td>
            <td>{{ $filters['status'] ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">Jenis Pendapatan</td>
            <td class="separator">:</td>
            <td>{{ $filters['jenis_pendapatan'] ?? '-' }}</td>
            <td></td>
            <td></td>
            <td></td>
        </tr>
        <tr>
            <td class="label">Kategori</td>
            <td class="separator">:</td>
            <td>{{ $filters['kategori'] ?? '-' }}</td>
            <td class="label">Tanggal Cetak</td>
            <td class="separator">:</td>
            <td>{{ $filters['tanggal_cetak'] ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">Kelas</td>
            <td class="separator">:</td>
            <td>{{ $filters['kelas'] ?? '-' }}</td>
            <td class="label">Bendahara</td>
            <td class="separator">:</td>
            <td>{{ $filters['bendahara'] ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">Periode Transaksi</td>
            <td class="separator">:</td>
            <td>{{ $filters['periode_transaksi'] ?? '-' }}</td>
            <td></td>
            <td></td>
            <td></td>
        </tr>
    </table>

    <table class="data-table" style="margin-top: 8px;">
        <thead>
            <tr>
                <th style="width: 4%;">No</th>
                <th style="width: 10%;">Tanggal Kuitansi</th>
                <th style="width: 14%;">Nomor Kuitansi</th>
                <th style="width: 18%;">Siswa</th>
                <th style="width: 10%;">Kapan Dibayarkan</th>
                <th style="width: 10%;">Nilai Tagihan</th>
                <th style="width: 10%;">Pembayaran</th>
                <th style="width: 16%;">Deskripsi</th>
                <th style="width: 8%;">Keterangan</th>
            </tr>
        </thead>
        <tbody>
            @forelse($receiptRows as $index => $row)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td class="text-center">
                        {{ $row['tanggal_kuitansi'] ? \Illuminate\Support\Carbon::parse($row['tanggal_kuitansi'])->format('d/m/Y') : '-' }}
                    </td>
                    <td>{{ $row['nomor_kuitansi'] }}</td>
                    <td>{{ $row['pelanggan'] }}</td>
                    <td class="text-center">
                        {{ $row['tanggal_bayar'] ? \Illuminate\Support\Carbon::parse($row['tanggal_bayar'])->format('d/m/Y') : '-' }}
                    </td>
                    <td class="text-right">{{ number_format($row['nilai_tagihan'], 0, ',', '.') }}</td>
                    <td class="text-right">{{ number_format($row['pembayaran'], 0, ',', '.') }}</td>
                    <td>
                        @php
                            $descItems = array_values(array_filter(array_map('trim', explode(';', $row['deskripsi'] ?? ''))));
                            $descItems = array_map(function ($item) {
                                $item = preg_replace('/\s*\((\d+)\s*-\s*(\d+)\)\s*/', ' ', $item);
                                $item = str_replace('_', ' ', (string) $item);
                                return trim((string) $item);
                            }, $descItems);
                        @endphp
                        @if(count($descItems) > 0)
                            <ul class="desc-list">
                                @foreach($descItems as $item)
                                    <li>{{ $item }}</li>
                                @endforeach
                            </ul>
                        @else
                            -
                        @endif
                    </td>
                    <td class="text-center">{{ $row['keterangan'] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="text-center">Tidak ada data</td>
                </tr>
            @endforelse
            <tr class="total-row">
                <td colspan="5" class="text-center">TOTAL</td>
                <td class="text-right">{{ number_format($receiptTotals['total_tagihan'] ?? 0, 0, ',', '.') }}</td>
                <td class="text-right">{{ number_format($receiptTotals['total_pembayaran'] ?? 0, 0, ',', '.') }}</td>
                <td colspan="2"></td>
            </tr>
        </tbody>
    </table>

    <table class="signature" style="width:100%; margin-top:30px;">
        <tr>
            <td style="width:100%; text-align:right;">

                <div style="display:inline-block; width:260px; text-align:center;">
                    <div style="font-weight:700;">
                        Kepala Unit Usaha
                    </div>

                    <div>
                        {{ $filters['unit_usaha'] ?? '-' }}
                    </div>

                    <div style="height:70px;"></div>

                    <div style="font-weight:700;">
                        Nama
                    </div>

                    <div>
                        NIP
                    </div>
                </div>

            </td>
        </tr>
    </table>
</div>
</body>
</html>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Pendapatan dan Penerimaan</title>
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
        .col-number th { font-weight: 600; font-size: 8px; }
        .total-row td { font-weight: 700; background: #f0f0f0; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .signature { margin-top: 18px; }
        .signature td { padding: 2px 4px; }
        .signature .title { font-size: 9px; font-weight: 700; text-align: left; }
        .signature .name { padding-top: 24px; font-weight: 700; }
        .page-break { page-break-after: always; }
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
                <div class="title">LAPORAN PENDAPATAN (REVENUE REPORT)</div>
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
            <td class="label">Tahun Anggaran</td>
            <td class="separator">:</td>
            <td>{{ $filters['tahun_anggaran'] ?? '-' }}</td>
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
                <th style="width: 12%;">Tanggal Invoice</th>
                <th style="width: 16%;">Nomor Invoice</th>
                <th style="width: 18%;">Pelanggan</th>
                <th style="width: 12%;">Jatuh Tempo</th>
                <th style="width: 12%;">Nominal</th>
                <th>Deskripsi</th>
            </tr>
            <tr class="col-number">
                <th>1</th>
                <th>2</th>
                <th>3</th>
                <th>4</th>
                <th>5</th>
                <th>6</th>
                <th>7</th>
            </tr>
        </thead>
        <tbody>
            @forelse($revenueRows as $index => $row)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td class="text-center">
                        {{ $row['tanggal_invoice'] ? \Illuminate\Support\Carbon::parse($row['tanggal_invoice'])->format('d/m/Y') : '-' }}
                    </td>
                    <td>{{ $row['nomor_invoice'] }}</td>
                    <td>{{ $row['pelanggan'] }}</td>
                    <td class="text-center">
                        {{ $row['jatuh_tempo'] ? \Illuminate\Support\Carbon::parse($row['jatuh_tempo'])->format('d/m/Y') : '-' }}
                    </td>
                    <td class="text-right">{{ number_format($row['nominal'], 0, ',', '.') }}</td>
                    <td>{{ $row['deskripsi'] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center">Tidak ada data</td>
                </tr>
            @endforelse
            <tr class="total-row">
                <td colspan="5" class="text-center">TOTAL</td>
                <td class="text-right">{{ number_format($revenueTotal ?? 0, 0, ',', '.') }}</td>
                <td></td>
            </tr>
        </tbody>
    </table>

    <table class="signature" style="width: 100%; margin-top: 16px;">
        <tr>
            <td style="width: 60%;"></td>
            <td class="title">Kepala Unit Usaha</td>
        </tr>
        <tr>
            <td></td>
            <td>{{ $filters['unit_usaha'] ?? '-' }}</td>
        </tr>
        <tr>
            <td></td>
            <td class="name">&nbsp;</td>
        </tr>
        <tr>
            <td></td>
            <td>Nama</td>
        </tr>
        <tr>
            <td></td>
            <td>NIP</td>
        </tr>
    </table>
</div>

<div class="page-break"></div>

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
            <td class="label">Tahun Anggaran</td>
            <td class="separator">:</td>
            <td>{{ $filters['tahun_anggaran'] ?? '-' }}</td>
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
                <th style="width: 12%;">Tanggal Kuitansi</th>
                <th style="width: 16%;">Nomor Kuitansi</th>
                <th style="width: 18%;">Pelanggan</th>
                <th style="width: 12%;">Nilai Tagihan</th>
                <th style="width: 12%;">Pembayaran</th>
                <th style="width: 16%;">Deskripsi</th>
                <th style="width: 10%;">Keterangan</th>
            </tr>
            <tr class="col-number">
                <th>1</th>
                <th>2</th>
                <th>3</th>
                <th>4</th>
                <th>5</th>
                <th>6</th>
                <th>7</th>
                <th>8</th>
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
                    <td class="text-right">{{ number_format($row['nilai_tagihan'], 0, ',', '.') }}</td>
                    <td class="text-right">{{ number_format($row['pembayaran'], 0, ',', '.') }}</td>
                    <td>{{ $row['deskripsi'] }}</td>
                    <td class="text-center">{{ $row['keterangan'] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center">Tidak ada data</td>
                </tr>
            @endforelse
            <tr class="total-row">
                <td colspan="4" class="text-center">TOTAL</td>
                <td class="text-right">{{ number_format($receiptTotals['total_tagihan'] ?? 0, 0, ',', '.') }}</td>
                <td class="text-right">{{ number_format($receiptTotals['total_pembayaran'] ?? 0, 0, ',', '.') }}</td>
                <td colspan="2"></td>
            </tr>
        </tbody>
    </table>

    <table class="signature" style="width: 100%; margin-top: 16px;">
        <tr>
            <td style="width: 60%;"></td>
            <td class="title">Kepala Unit Usaha</td>
        </tr>
        <tr>
            <td></td>
            <td>{{ $filters['unit_usaha'] ?? '-' }}</td>
        </tr>
        <tr>
            <td></td>
            <td class="name">&nbsp;</td>
        </tr>
        <tr>
            <td></td>
            <td>Nama</td>
        </tr>
        <tr>
            <td></td>
            <td>NIP</td>
        </tr>
    </table>
</div>
</body>
</html>

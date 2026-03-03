<div class="payment-header">
    <table width="100%">
        <tr>
            <td style="width:60px; vertical-align: middle;">
                @php
                    // If caller already provided a logoFile (e.g. asset URL for web views), use it directly.
                    // Otherwise resolve a local filesystem path so Dompdf can read it.
                    if (! isset($logoFile)) {
                        $logoFile = public_path('images/LOGO-PAUD-PERMATA.png');
                        if (! file_exists($logoFile)) {
                            $logoFile = public_path('images/logo.png');
                        }
                    }
                @endphp

                @if($logoFile && (Str::startsWith($logoFile, 'http') || file_exists($logoFile)))
                    <img src="{{ $logoFile }}" alt="logo" style="width:60px; height:auto;" />
                @endif
            </td>
            <td style="vertical-align: middle;">
                <div class="payment-school">{{ strtoupper(config('app.name')) }}</div>
                <div class="payment-subtitle">{{ strtoupper(config('app.subtitle', 'UNIVERSITAS DIPONEGORO')) }}</div>
            </td>
            <td class="payment-title" style="vertical-align: middle;">
                <h2>{{ $title ?? 'BUKTI PEMBAYARAN' }}</h2>
            </td>
        </tr>
        <tr>
            <td colspan="3" class="payment-address">
                {{ config('app.address', 'Jl. Prof. Sudarto SH. Tembalang Semarang') }}
            </td>
        </tr>
    </table>
</div>

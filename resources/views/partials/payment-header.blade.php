<div class="payment-header">
    <table width="100%">
        <tr>
            <td>
                @php
                    $logoFile = null;
                    if (file_exists(public_path('images/LOGO-PAUD-PERMATA.png'))) {
                        $logoFile = asset('images/LOGO-PAUD-PERMATA.png');
                    } elseif (file_exists(public_path('images/logo.png'))) {
                        $logoFile = asset('images/logo.png');
                    }
                @endphp

                @if($logoFile)
                    <img src="{{ $logoFile }}" alt="logo" style="width:60px; height:auto; margin-bottom:4px;" />
                @endif

                <div class="payment-school">{{ strtoupper(config('app.name')) }}</div>
                <div>{{ config('app.address', 'Jl. Prof. Sudarto SH. Tembalang Semarang') }}</div>
            </td>
            <td class="payment-title">
                <h2>{{ $title ?? 'BUKTI PEMBAYARAN' }}</h2>
            </td>
        </tr>
    </table>
</div>

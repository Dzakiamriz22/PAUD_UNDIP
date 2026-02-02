<div class="payment-header">
    <table width="100%">
        <tr>
            <td>
                @if(file_exists(public_path('images/LOGO-PAUD-PERMATA.jpeg')))
                    <img src="{{ public_path('images/LOGO-PAUD-PERMATA.jpeg') }}" alt="logo" style="width:60px; height:auto; margin-bottom:4px;" />
                @endif
                <div class="payment-school">{{ strtoupper(config('app.name')) }}</div>
                <div>{{ config('app.address', 'Alamat Sekolah') }}</div>
            </td>
            <td class="payment-title">
                <h2>{{ $title ?? 'BUKTI PEMBAYARAN' }}</h2>
            </td>
        </tr>
    </table>
</div>

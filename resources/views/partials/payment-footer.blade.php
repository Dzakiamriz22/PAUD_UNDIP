<div class="footer">
    <div class="signature">
        <div>{{ $signatureDate ?? now()->format('d F Y') }}</div>
        <div class="name">{{ $signatureName ?? (auth()->user()->name ?? 'Petugas') }}</div>
        <div>{{ $signatureRole ?? 'Bendahara' }}</div>
    </div>
    <div class="clear"></div>
</div>

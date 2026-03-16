<div class="footer">
    <div class="signature">
        <div>{{ $signatureDate ?? now()->format('d F Y') }}</div>
        @php
            $bendaharaUsers = \App\Models\User::role('bendahara')->get();
            $bendaharaUser = auth()->user()?->hasRole('bendahara')
                ? auth()->user()
                : $bendaharaUsers->sortBy('firstname')->first();

            $bendaharaName = $bendaharaUser
                ? trim(implode(' ', array_filter([$bendaharaUser->firstname, $bendaharaUser->lastname])))
                : 'Bendahara';
        @endphp
        <div class="name">{{ $signatureName ?? $bendaharaName }}</div>
        <div>{{ $signatureRole ?? 'Bendahara' }}</div>
    </div>
    <div class="clear"></div>
</div>

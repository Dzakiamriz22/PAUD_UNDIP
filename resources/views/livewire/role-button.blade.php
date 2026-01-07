<x-filament::button
    href="{{ route('filament.admin.pages.role-switcher') }}"
    wire:navigate
    tag="a"
    size="sm"
    color="danger"
    x-data="{ role: $wire.entangle('active_role').live }"
    x-show="role"
    outlined
>
   Role : {{ strtoupper(str_replace('_', ' ', $this->active_role)) }}
</x-filament::button>

<x-filament::button
    href="{{ '#' }}"
    wire:navigate
    tag="a"
    size="sm"
    color="danger"
    outlined
>
{{ !empty($this->roles) 
    ? implode(', ', array_map(fn($role) => strtoupper(str_replace('_', ' ', $role)), $this->roles)) 
    : 'UNKNOWN' }}
</x-filament::button>
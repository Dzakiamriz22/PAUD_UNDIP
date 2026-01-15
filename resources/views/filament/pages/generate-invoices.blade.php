<x-filament::page>
    <form wire:submit.prevent="generate">
        {{ $this->form }}

        <x-filament::button
            type="submit"
            color="primary"
            class="mt-6"
        >
            Generate Invoice
        </x-filament::button>
    </form>
</x-filament::page>
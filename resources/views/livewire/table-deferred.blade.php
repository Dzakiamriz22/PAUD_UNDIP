<div wire:init="loadTable" class="flex flex-col gap-y-6">
    <form wire:submit.prevent="selectPegawai">
        {{ $this->table }}
    </form>
</div>
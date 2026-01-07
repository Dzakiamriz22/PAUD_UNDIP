<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
>
    <div
        x-data="{
            open: false,
            disabled: @js($field->isDisabled()), // <-- cek apakah field disabled
            value: $wire.{{ $applyStateBindingModifiers("\$entangle('{$getStatePath()}')") }},
            options: @js($getOptions()),
            select(option) {
                if (this.disabled) return   // <-- cegah klik kalau disabled
                this.value = option.id
                this.selectedId = option.id
                this.open = false
            },
            displayLabel() {
                const found = this.options.find(o => String(o.id) === String(this.value))
                return found?.label ?? 'Select an option'
            },
            selectedId: null,
        }"
        x-init="selectedId = value"
        class="relative w-full"
        x-on:keydown.escape.window="open = false"
    >
        <!-- Trigger -->
        <button
            type="button"
            @click="if (!disabled) open = !open"
            :disabled="disabled"
            class="w-full border rounded-lg px-3 py-2 text-left text-sm"
            :class="disabled ? 'bg-gray-50 text-gray-400 cursor-not-allowed' : ''"
        >
            <span x-text="displayLabel()"></span>
        </button>

        <!-- Dropdown tabel -->
        <div
            x-show="open && !disabled"
            x-on:click.outside="open = false"
            class="absolute z-50 mt-1 w-full bg-white border rounded-lg shadow-lg max-h-60 overflow-y-auto"
        >
            <table class="min-w-full text-sm">
                <thead class="bg-gray-100 sticky top-0">
                    <tr>
                        <th class="px-2 py-1 text-left">Nama</th>
                        <th class="px-2 py-1 text-left">Instansi</th>
                        <th class="px-2 py-1 text-left">Tempat</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="option in options" :key="option.id">
                        <tr
                            class="cursor-pointer hover:bg-gray-100"
                            :class="selectedId === option.id ? 'bg-yellow-50' : ''"
                            @click="select(option)"
                        >
                            <td class="px-2 py-1" x-text="option.nama ?? ''"></td>
                            <td class="px-2 py-1" x-text="option.instansi ?? ''"></td>
                            <td class="px-2 py-1" x-text="option.tempat ?? ''"></td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>
</x-dynamic-component>

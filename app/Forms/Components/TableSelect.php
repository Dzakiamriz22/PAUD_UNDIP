<?php

namespace App\Forms\Components;

use Closure;
use Filament\Forms\Components\Field;

class TableSelect extends Field
{
    protected string $view = 'forms.components.table-select';

    /** @var array|Closure */
    protected array|Closure $options = [];

    /**
     * Setter: bisa terima array biasa ATAU Closure (biar lazy).
     */
    public function options(array|Closure $options): static
    {
        $this->options = $options;

        return $this;
    }

    /**
     * Getter yang dipanggil di Blade: $getOptions()
     */
    public function getOptions(): array
    {
        // evaluate() akan menjalankan Closure jika diberikan Closure
        $options = $this->evaluate($this->options) ?? [];

        // Pastikan formatnya array of:
        // ['id' => ..., 'nama' => ..., 'instansi' => ..., 'tempat' => ..., 'label' => ...]
        return collect($options)->map(function ($item) {
            // Kalau item sudah sesuai format, lewati
            if (is_array($item) && isset($item['id'])) {
                return $item;
            }

            // Kalau item adalah model Eloquent
            if (is_object($item) && isset($item->id)) {
                $nama = $item->nama_tujuan ?? $item->nama ?? '';
                $inst = $item->instansi_tujuan ?? $item->instansi ?? '';
                $tempat = $item->tempat_tujuan ?? $item->tempat ?? '';

                return [
                    'id'       => $item->id,
                    'nama'     => $nama,
                    'instansi' => $inst,
                    'tempat'     => $tempat,
                    'label'    => trim($nama . ", " . $inst . ", " . $tempat),
                ];
            }

            // Fallback: item string/sederhana
            return [
                'id'    => is_array($item) ? ($item['id'] ?? null) : $item,
                'label' => is_array($item) ? ($item['label'] ?? (string) $item) : (string) $item,
            ];
        })->values()->all();
    }
}

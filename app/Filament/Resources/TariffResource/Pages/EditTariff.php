<?php

namespace App\Filament\Resources\TariffResource\Pages;

use App\Filament\Resources\TariffResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

class EditTariff extends EditRecord
{
    protected static string $resource = TariffResource::class;

    /**
     * Dipanggil TEPAT sebelum data disimpan ke database
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Jika tarif sebelumnya ditolak dan diedit bendahara/admin
        if (
            $this->record->status === 'rejected'
            && Auth::user()->hasRole(['admin', 'bendahara'])
        ) {
            $data['status'] = 'pending';     // ajukan ulang
            $data['is_active'] = false;      // pastikan tidak aktif
            $data['approved_by'] = null;
            $data['approved_at'] = null;
        }

        return $data;
    }
}

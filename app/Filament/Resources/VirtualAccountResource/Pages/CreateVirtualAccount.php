<?php

namespace App\Filament\Resources\VirtualAccountResource\Pages;

use App\Filament\Resources\VirtualAccountResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;
use App\Models\VirtualAccount;

class CreateVirtualAccount extends CreateRecord
{
    protected static string $resource = VirtualAccountResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (VirtualAccount::count() > 0) {
            throw ValidationException::withMessages([
                'va_number' => 'Virtual Account sudah ada. Sistem hanya mengizinkan satu VA aktif.',
            ]);
        }

        // Prevent duplicate VA with same bank_name + va_number
        if (isset($data['bank_name'], $data['va_number'])) {
            $exists = VirtualAccount::where('bank_name', $data['bank_name'])
                ->where('va_number', $data['va_number'])
                ->exists();

            if ($exists) {
                throw ValidationException::withMessages([
                    'va_number' => 'Virtual Account dengan bank dan nomor yang sama sudah ada.',
                ]);
            }
        }

        return $data;
    }
}

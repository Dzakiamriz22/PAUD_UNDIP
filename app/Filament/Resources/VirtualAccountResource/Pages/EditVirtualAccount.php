<?php

namespace App\Filament\Resources\VirtualAccountResource\Pages;

use App\Filament\Resources\VirtualAccountResource;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Validation\ValidationException;
use App\Models\VirtualAccount;

class EditVirtualAccount extends EditRecord
{
    protected static string $resource = VirtualAccountResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Prevent duplicate VA with same bank_name + va_number, excluding current record
        $recordId = $this->record?->id;
        if (isset($data['bank_name'], $data['va_number'])) {
            $exists = VirtualAccount::where('bank_name', $data['bank_name'])
                ->where('va_number', $data['va_number'])
                ->where('id', '!=', $recordId)
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

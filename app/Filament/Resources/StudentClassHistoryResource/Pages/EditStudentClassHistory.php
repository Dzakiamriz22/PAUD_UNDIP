<?php

namespace App\Filament\Resources\StudentClassHistoryResource\Pages;

use App\Filament\Resources\StudentClassHistoryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditStudentClassHistory extends EditRecord
{
    protected static string $resource = StudentClassHistoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}

<?php

namespace App\Filament\Resources\StudentClassHistoryResource\Pages;

use App\Filament\Resources\StudentClassHistoryResource;
use Filament\Resources\Pages\ListRecords;

class ListStudentClassHistories extends ListRecords
{
    protected static string $resource = StudentClassHistoryResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}

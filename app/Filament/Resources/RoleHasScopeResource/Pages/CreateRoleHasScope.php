<?php

namespace App\Filament\Resources\RoleHasScopeResource\Pages;

use App\Filament\Resources\RoleHasScopeResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateRoleHasScope extends CreateRecord
{
    protected static string $resource = RoleHasScopeResource::class;

    protected function getFormActions(): array
    {
        return [
            // ...parent::getFormActions(),
            parent::getCreateFormAction()
                ->label('Save Record')
                ->color('success')
                ->icon('heroicon-o-check-circle'),
            parent::getCancelFormAction()
                ->label('Back')
                ->icon('heroicon-o-chevron-left'),
        ];
    }
}

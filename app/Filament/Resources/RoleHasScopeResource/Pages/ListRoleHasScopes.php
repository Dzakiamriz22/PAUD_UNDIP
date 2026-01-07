<?php

namespace App\Filament\Resources\RoleHasScopeResource\Pages;

use App\Filament\Resources\RoleHasScopeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRoleHasScopes extends ListRecords
{
    protected static string $resource = RoleHasScopeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Create Record')
                ->color('primary')
                ->icon('heroicon-o-plus-circle'),
        ];
    }
}

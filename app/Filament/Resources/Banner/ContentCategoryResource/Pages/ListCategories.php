<?php

namespace App\Filament\Resources\Banner\ContentCategoryResource\Pages;

use App\Filament\Resources\Banner\ContentCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Pages\EditRecord;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Pages\ViewRecord;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListCategories extends ListRecords
{
    protected static string $resource = ContentCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Create Record')
                ->color('primary')
                ->icon('heroicon-o-plus-circle'),
        ];
    }

    public function getTitle(): string
    {
        return __('Banner Categories Management');
    }

    public function getTabs(): array
    {
        $tabs = [
            'all' => Tab::make()->modifyQueryUsing(function (Builder $query) {
                /** @var \App\Models\Category $query */
                $query->withoutTrashed();
            }),
            'trashed' => Tab::make()->modifyQueryUsing(function (Builder $query) {
                /** @var \App\Models\Category $query */
                $query->onlyTrashed();
            })->icon('heroicon-o-trash'),
        ];

        return $tabs;
    }

    public function updatedActiveTab(): void
    {
        $this->resetPage();
        $this->deselectAllTableRecords();
    }
}
